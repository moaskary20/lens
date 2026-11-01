import 'dart:async';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Shows Lens inbox alerts in the Android / iOS system tray.
class PushAlerts {
  PushAlerts._();

  static final PushAlerts instance = PushAlerts._();

  static const _channelId = 'lens_alerts';
  static const _channelName = 'Lens alerts';
  static const _prefsEnabled = 'lens_push_alerts_enabled';
  static const _prefsDevice = 'lens_device_token';
  // v2: older builds silently marked inbox items as announced without showing them.
  static const _prefsAnnounced = 'lens_announced_notice_ids_v2';
  static const _pollEvery = Duration(seconds: 20);

  final FlutterLocalNotificationsPlugin _plugin = FlutterLocalNotificationsPlugin();
  final Set<String> _announced = <String>{};
  bool _ready = false;
  bool _started = false;
  bool _enabled = true;
  bool _announcedLoaded = false;
  Timer? _poll;
  String? _deviceToken;
  VoidCallback? _onOpenInbox;

  bool get enabled => _enabled;

  /// Warm up the plugin + Android permission as early as possible.
  Future<void> bootstrap() async {
    await _loadPrefs();
    await ensureReady();
  }

  Future<void> start({VoidCallback? onOpenInbox}) async {
    _onOpenInbox = onOpenInbox;
    if (_started) {
      _restartPoll();
      unawaited(syncFromInbox(NotificationsStore.instance.items));
      return;
    }
    _started = true;
    SessionStore.instance.addListener(_onSession);
    NotificationsStore.instance.addListener(_onInboxChanged);
    await _loadPrefs();
    await ensureReady();
    await syncDeviceToken();
    _restartPoll();
    // Show any unread already in memory, then refresh from API.
    await syncFromInbox(NotificationsStore.instance.items);
    unawaited(NotificationsStore.instance.refresh());
  }

  void stop() {
    _poll?.cancel();
    _poll = null;
    if (!_started) {
      return;
    }
    _started = false;
    SessionStore.instance.removeListener(_onSession);
    NotificationsStore.instance.removeListener(_onInboxChanged);
  }

  Future<void> setEnabled(bool value) async {
    _enabled = value;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_prefsEnabled, value);
    if (!value) {
      await _plugin.cancelAll();
      return;
    }
    await ensureReady();
    await syncFromInbox(NotificationsStore.instance.items);
  }

  Future<void> ensureReady() async {
    if (kIsWeb) {
      return;
    }
    if (!Platform.isAndroid && !Platform.isIOS) {
      return;
    }

    if (!_ready) {
      const android = AndroidInitializationSettings('@mipmap/ic_launcher');
      const ios = DarwinInitializationSettings(
        requestAlertPermission: true,
        requestBadgePermission: true,
        requestSoundPermission: true,
      );
      await _plugin.initialize(
        settings: const InitializationSettings(android: android, iOS: ios),
        onDidReceiveNotificationResponse: _onTap,
      );
      _ready = true;
    }

    if (Platform.isAndroid) {
      final androidPlugin = _plugin.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>();
      await androidPlugin?.createNotificationChannel(
        const AndroidNotificationChannel(
          _channelId,
          _channelName,
          description: 'Bookings, messages, and account alerts from Lens',
          importance: Importance.high,
          playSound: true,
          enableVibration: true,
          showBadge: true,
        ),
      );
      final allowed = await androidPlugin?.areNotificationsEnabled();
      if (allowed != true) {
        await androidPlugin?.requestNotificationsPermission();
      }
    }
  }

  Future<void> syncFromInbox(List<AppNotice> items) async {
    if (!_enabled || SessionStore.instance.isGuest) {
      return;
    }
    await ensureReady();
    if (!_ready) {
      return;
    }
    await _ensureAnnouncedLoaded();

    var changed = false;
    for (final item in items) {
      if (item.read || item.id.isEmpty || _announced.contains(item.id)) {
        continue;
      }
      _announced.add(item.id);
      changed = true;
      await _show(item);
    }
    if (changed) {
      await _persistAnnounced();
    }
  }

  /// Force a tray notification — useful after login / settings toggle.
  Future<void> previewLatestUnread() async {
    if (!_enabled || SessionStore.instance.isGuest) {
      return;
    }
    await ensureReady();
    final unread = NotificationsStore.instance.items.where((item) => !item.read);
    if (unread.isEmpty) {
      return;
    }
    final latest = unread.first;
    // Allow re-showing the latest unread once when user enables pushes.
    _announced.remove(latest.id);
    await syncFromInbox(NotificationsStore.instance.items);
  }

  Future<void> syncDeviceToken() async {
    if (!LensConfig.useNetwork || SessionStore.instance.isGuest) {
      return;
    }
    if (!Platform.isAndroid && !Platform.isIOS) {
      return;
    }
    final token = await _deviceId();
    try {
      await ApiClient().postJson('/app/device-token', {
        'token': token,
        'platform': Platform.isIOS ? 'ios' : 'android',
      });
    } catch (_) {}
  }

  Future<void> clearDeviceToken() async {
    if (!LensConfig.useNetwork || _deviceToken == null) {
      return;
    }
    try {
      await ApiClient().deleteJson('/app/device-token', body: {'token': _deviceToken!});
    } catch (_) {}
  }

  void resetAnnounced() {
    _announced.clear();
    _announcedLoaded = false;
    unawaited(_persistAnnounced());
  }

  @visibleForTesting
  void reset() {
    stop();
    _announced.clear();
    _announcedLoaded = false;
    _ready = false;
    _started = false;
    _enabled = true;
    _deviceToken = null;
    _onOpenInbox = null;
  }

  Future<void> _loadPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    _enabled = prefs.getBool(_prefsEnabled) ?? true;
    _deviceToken = prefs.getString(_prefsDevice);
  }

  Future<void> _ensureAnnouncedLoaded() async {
    if (_announcedLoaded) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getStringList(_prefsAnnounced) ?? const <String>[];
    _announced
      ..clear()
      ..addAll(saved);
    _announcedLoaded = true;
  }

  Future<void> _persistAnnounced() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_prefsAnnounced, _announced.toList(growable: false));
  }

  Future<String> _deviceId() async {
    if (_deviceToken != null && _deviceToken!.isNotEmpty) {
      return _deviceToken!;
    }
    final prefs = await SharedPreferences.getInstance();
    final existing = prefs.getString(_prefsDevice);
    if (existing != null && existing.isNotEmpty) {
      _deviceToken = existing;
      return existing;
    }
    final created = 'lens-local-${DateTime.now().microsecondsSinceEpoch}';
    await prefs.setString(_prefsDevice, created);
    _deviceToken = created;
    return created;
  }

  Future<void> _show(AppNotice item) async {
    final details = NotificationDetails(
      android: AndroidNotificationDetails(
        _channelId,
        _channelName,
        channelDescription: 'Bookings, messages, and account alerts from Lens',
        importance: Importance.high,
        priority: Priority.high,
        playSound: true,
        enableVibration: true,
        category: AndroidNotificationCategory.message,
        visibility: NotificationVisibility.public,
        styleInformation: BigTextStyleInformation(
          item.body.isEmpty ? item.title : item.body,
          contentTitle: item.title,
          summaryText: 'Lens',
        ),
        ticker: item.title,
      ),
      iOS: const DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      ),
    );

    final id = item.id.hashCode & 0x7fffffff;
    await _plugin.show(
      id: id == 0 ? item.title.hashCode & 0x7fffffff : id,
      title: item.title,
      body: item.body,
      notificationDetails: details,
      payload: 'inbox',
    );
  }

  void _onTap(NotificationResponse response) {
    if (response.payload == 'inbox') {
      _onOpenInbox?.call();
    }
  }

  void _onSession() {
    if (SessionStore.instance.isGuest) {
      resetAnnounced();
      _poll?.cancel();
      unawaited(clearDeviceToken());
      return;
    }
    unawaited(syncDeviceToken());
    _restartPoll();
    unawaited(() async {
      await NotificationsStore.instance.refresh();
      await syncFromInbox(NotificationsStore.instance.items);
    }());
  }

  void _onInboxChanged() {
    unawaited(syncFromInbox(NotificationsStore.instance.items));
  }

  void _restartPoll() {
    _poll?.cancel();
    if (SessionStore.instance.isGuest) {
      return;
    }
    _poll = Timer.periodic(_pollEvery, (_) {
      if (!SessionStore.instance.isGuest) {
        unawaited(NotificationsStore.instance.refresh());
      }
    });
  }
}
