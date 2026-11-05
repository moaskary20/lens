import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/io_stub.dart' if (dart.library.io) 'dart:io';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

@pragma('vm:entry-point')
Future<void> lensFirebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {}
}

/// Shows Lens inbox alerts in the Android / iOS system tray (FCM + local poll).
class PushAlerts {
  PushAlerts._();

  static final PushAlerts instance = PushAlerts._();

  // v2 channel: Android locks sound/importance on first create; bump id to restore sound.
  static const _channelId = 'lens_alerts_v2';
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
  bool _firebaseReady = false;
  StreamSubscription<RemoteMessage>? _foregroundSub;
  StreamSubscription<String>? _tokenRefreshSub;
  Timer? _poll;
  String? _deviceToken;
  VoidCallback? _onOpenInbox;
  bool _openInboxPending = false;
  bool _messageOpenedBound = false;

  bool get enabled => _enabled;
  bool get firebaseReady => _firebaseReady;

  /// Warm up Firebase + local notifications as early as possible.
  Future<void> bootstrap() async {
    await _loadPrefs();
    await _initFirebase();
    await ensureReady();
  }

  Future<void> start({VoidCallback? onOpenInbox}) async {
    _onOpenInbox = onOpenInbox;
    if (_openInboxPending) {
      _openInboxPending = false;
      // Defer one frame so navigation context is ready.
      scheduleMicrotask(_openInbox);
    }
    if (_started) {
      _restartPoll();
      unawaited(syncFromInbox(NotificationsStore.instance.items));
      return;
    }
    _started = true;
    SessionStore.instance.addListener(_onSession);
    NotificationsStore.instance.addListener(_onInboxChanged);
    await _loadPrefs();
    await _initFirebase();
    await ensureReady();
    await syncDeviceToken();
    _listenForeground();
    await _consumeInitialMessage();
    _restartPoll();
    await syncFromInbox(NotificationsStore.instance.items);
    unawaited(NotificationsStore.instance.refresh());
  }

  void stop() {
    _poll?.cancel();
    _poll = null;
    _foregroundSub?.cancel();
    _foregroundSub = null;
    _tokenRefreshSub?.cancel();
    _tokenRefreshSub = null;
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

    if (_firebaseReady) {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission(alert: true, badge: true, sound: true);
      await messaging.setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );
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
      await _show(item.title, item.body, payload: 'inbox', noticeId: item.id);
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
    final token = await _resolvePushToken();
    if (token == null || token.isEmpty || token.startsWith('lens-local-')) {
      // Don't register placeholder tokens — FCM must be ready for remote push.
      return;
    }
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
    if (_deviceToken!.startsWith('lens-local-')) {
      _deviceToken = null;
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
    _firebaseReady = false;
    _deviceToken = null;
    _onOpenInbox = null;
    _openInboxPending = false;
    _messageOpenedBound = false;
  }

  Future<void> _initFirebase() async {
    if (kIsWeb || _firebaseReady) {
      return;
    }
    if (!Platform.isAndroid && !Platform.isIOS) {
      return;
    }
    try {
      if (Firebase.apps.isEmpty) {
        await Firebase.initializeApp();
      }
      FirebaseMessaging.onBackgroundMessage(lensFirebaseMessagingBackgroundHandler);
      _firebaseReady = true;
      _tokenRefreshSub?.cancel();
      _tokenRefreshSub = FirebaseMessaging.instance.onTokenRefresh.listen((token) {
        _deviceToken = token;
        unawaited(_persistDeviceToken(token));
        unawaited(syncDeviceToken());
      });
    } catch (error, stack) {
      debugPrint('Firebase init failed: $error\n$stack');
      _firebaseReady = false;
    }
  }

  void _listenForeground() {
    if (!_firebaseReady) {
      return;
    }
    _foregroundSub?.cancel();
    _foregroundSub = FirebaseMessaging.onMessage.listen((message) async {
      if (!_enabled) {
        return;
      }
      final title = message.notification?.title ?? message.data['title']?.toString() ?? 'Lens';
      final body = message.notification?.body ?? message.data['body']?.toString() ?? '';
      final event = message.data['event']?.toString() ?? 'app_notice';
      final id = message.messageId ?? '${title}_$body';
      NotificationsStore.instance.ingestPush(title: title, body: body, id: id, event: event);
      await _show(title, body, payload: 'inbox', noticeId: id);
    });
    if (!_messageOpenedBound) {
      _messageOpenedBound = true;
      FirebaseMessaging.onMessageOpenedApp.listen((message) {
        _ingestRemoteMessage(message);
        _openInbox();
      });
    }
  }

  Future<void> _consumeInitialMessage() async {
    if (!_firebaseReady) {
      return;
    }
    try {
      final initial = await FirebaseMessaging.instance.getInitialMessage();
      if (initial == null) {
        return;
      }
      _ingestRemoteMessage(initial);
      _openInbox();
    } catch (error) {
      debugPrint('FCM getInitialMessage failed: $error');
    }
  }

  void _ingestRemoteMessage(RemoteMessage message) {
    final title = message.notification?.title ?? message.data['title']?.toString() ?? 'Lens';
    final body = message.notification?.body ?? message.data['body']?.toString() ?? '';
    final event = message.data['event']?.toString() ?? 'app_notice';
    final id = message.messageId ?? message.data['notification_id']?.toString();
    NotificationsStore.instance.ingestPush(title: title, body: body, id: id, event: event);
  }

  void _openInbox() {
    unawaited(NotificationsStore.instance.openAndClear());
    final open = _onOpenInbox;
    if (open == null) {
      _openInboxPending = true;
      return;
    }
    open();
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

  Future<void> _persistDeviceToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsDevice, token);
  }

  Future<String?> _resolvePushToken() async {
    if (_deviceToken != null &&
        _deviceToken!.isNotEmpty &&
        !_deviceToken!.startsWith('lens-local-')) {
      return _deviceToken;
    }
    if (_firebaseReady) {
      try {
        final token = await FirebaseMessaging.instance.getToken();
        if (token != null && token.isNotEmpty) {
          _deviceToken = token;
          await _persistDeviceToken(token);
          return token;
        }
      } catch (error) {
        debugPrint('FCM getToken failed: $error');
      }
    }
    return null;
  }

  Future<void> _show(
    String title,
    String body, {
    String payload = 'inbox',
    String? noticeId,
  }) async {
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
          body.isEmpty ? title : body,
          contentTitle: title,
          summaryText: 'Lens',
        ),
        ticker: title,
      ),
      iOS: const DarwinNotificationDetails(
        presentAlert: true,
        presentBadge: true,
        presentSound: true,
      ),
    );

    final raw = (noticeId ?? title).hashCode & 0x7fffffff;
    await _plugin.show(
      id: raw == 0 ? title.hashCode & 0x7fffffff : raw,
      title: title,
      body: body,
      notificationDetails: details,
      payload: payload,
    );
  }

  void _onTap(NotificationResponse response) {
    if (response.payload == 'inbox') {
      unawaited(_plugin.cancelAll());
      _openInbox();
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
    unawaited(_syncInboxAndClearRead());
  }

  Future<void> _syncInboxAndClearRead() async {
    final items = NotificationsStore.instance.items;
    await syncFromInbox(items);
    if (!_ready) {
      return;
    }
    for (final item in items) {
      if (!item.read || item.id.isEmpty) {
        continue;
      }
      final id = item.id.hashCode & 0x7fffffff;
      await _plugin.cancel(id: id == 0 ? item.title.hashCode & 0x7fffffff : id);
    }
    if (NotificationsStore.instance.unread == 0) {
      await _plugin.cancelAll();
    }
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
