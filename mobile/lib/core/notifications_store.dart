import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

class AppNotice {
  const AppNotice({
    required this.id,
    required this.title,
    required this.body,
    required this.event,
    required this.read,
    this.createdAt,
  });

  factory AppNotice.fromJson(Map<String, dynamic> json) {
    return AppNotice(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Lens',
      body: json['body']?.toString() ?? '',
      event: json['event']?.toString() ?? 'general',
      read: json['read'] == true,
      createdAt: json['created_at']?.toString(),
    );
  }

  final String id;
  final String title;
  final String body;
  final String event;
  final bool read;
  final String? createdAt;

  AppNotice copyWith({bool? read}) {
    return AppNotice(id: id, title: title, body: body, event: event, read: read ?? this.read, createdAt: createdAt);
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'body': body,
        'event': event,
        'read': read,
        'created_at': createdAt,
      };
}

class NotificationsStore extends ChangeNotifier {
  NotificationsStore._() {
    SessionStore.instance.addListener(_onSessionChanged);
  }

  static final NotificationsStore instance = NotificationsStore._();

  static const _cacheKeyPrefix = 'lens_notifications_cache_v1_';

  List<AppNotice> _items = [];
  int _unread = 0;
  ApiClient _api = ApiClient();
  final Set<String> _pendingReadIds = <String>{};
  Future<void>? _refreshInFlight;
  bool _loading = false;
  String? _error;
  bool _loadedOnce = false;
  bool _cacheLoaded = false;

  List<AppNotice> get items => SessionStore.instance.isGuest ? const [] : List.unmodifiable(_items);

  int get unread => SessionStore.instance.isGuest ? 0 : _unread;

  bool get loading => _loading;

  String? get error => SessionStore.instance.isGuest ? null : _error;

  bool get loadedOnce => _loadedOnce;

  String? get _cacheKey {
    final email = SessionStore.instance.email?.trim();
    if (email == null || email.isEmpty) {
      return null;
    }
    return '$_cacheKeyPrefix${email.toLowerCase()}';
  }

  void hydrate({int unread = 0}) {
    if (SessionStore.instance.isGuest) {
      _clearGuest();
      return;
    }
    if (_items.isEmpty && !LensConfig.useNetwork) {
      _items = _demo;
      _unread = unread > 0 ? unread : _items.where((item) => !item.read).length;
      _loadedOnce = true;
    } else if (_pendingReadIds.isEmpty) {
      final localUnread = _items.where((item) => !item.read).length;
      _unread = _items.isEmpty ? unread : (localUnread < unread ? localUnread : unread);
    }
    notifyListeners();
    unawaited(_restoreCacheThenRefresh());
  }

  Future<void> _restoreCacheThenRefresh() async {
    await _restoreCache();
    if (LensConfig.useNetwork && !SessionStore.instance.isGuest) {
      await refresh();
    }
  }

  Future<void> _restoreCache() async {
    if (_cacheLoaded || !LensConfig.useNetwork || SessionStore.instance.isGuest) {
      return;
    }
    _cacheLoaded = true;
    final key = _cacheKey;
    if (key == null) {
      return;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(key);
      if (raw == null || raw.isEmpty || _items.isNotEmpty) {
        return;
      }
      final decoded = jsonDecode(raw);
      if (decoded is! Map) {
        return;
      }
      final cached = (decoded['notifications'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => AppNotice.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      if (cached.isEmpty) {
        return;
      }
      _items = cached;
      _unread = decoded['unread'] is int
          ? decoded['unread'] as int
          : cached.where((item) => !item.read).length;
      _loadedOnce = true;
      _error = null;
      notifyListeners();
    } catch (_) {}
  }

  Future<void> _persistCache() async {
    final key = _cacheKey;
    if (key == null || !LensConfig.useNetwork) {
      return;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(
        key,
        jsonEncode({
          'unread': _unread,
          'notifications': [for (final item in _items.take(80)) item.toJson()],
        }),
      );
    } catch (_) {}
  }

  Future<void> refresh() {
    final existing = _refreshInFlight;
    if (existing != null) {
      return existing;
    }
    final run = _refreshBody();
    _refreshInFlight = run.whenComplete(() {
      if (identical(_refreshInFlight, run)) {
        _refreshInFlight = null;
      }
    });
    return _refreshInFlight!;
  }

  Future<void> _refreshBody() async {
    if (SessionStore.instance.isGuest) {
      _clearGuest();
      return;
    }
    if (!LensConfig.useNetwork) {
      if (_items.isEmpty) {
        _items = _demo;
        _unread = _items.where((item) => !item.read).length;
      }
      _loading = false;
      _error = null;
      _loadedOnce = true;
      notifyListeners();
      return;
    }

    final email = SessionStore.instance.email?.trim() ?? '';
    if (email.isEmpty) {
      _loading = false;
      _error = 'Sign in again to load notifications.';
      _loadedOnce = true;
      notifyListeners();
      return;
    }

    _loading = true;
    _error = null;
    notifyListeners();

    try {
      final payload = await _api.getJson('/app/notifications').timeout(const Duration(seconds: 12));
      final raw = payload['notifications'];
      final remote = (raw is List ? raw : const [])
          .whereType<Map>()
          .map((item) => AppNotice.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      _items = [
        for (final item in remote)
          _pendingReadIds.contains(item.id) || item.read ? item.copyWith(read: true) : item,
      ];
      final localUnread = _items.where((item) => !item.read).length;
      final serverUnread = payload['unread'] is int
          ? payload['unread'] as int
          : int.tryParse('${payload['unread']}') ?? localUnread;
      _unread = _pendingReadIds.isEmpty ? serverUnread : localUnread;
      _error = null;
      unawaited(_persistCache());
    } catch (error) {
      _error = error.toString().replaceFirst('ApiException: ', '').replaceFirst('TimeoutException: ', 'Request timed out. ');
      // Keep any previously loaded / cached items so the screen is not blank.
    } finally {
      _loading = false;
      _loadedOnce = true;
      notifyListeners();
    }
  }

  Future<void> markRead(AppNotice notice) async {
    if (SessionStore.instance.isGuest || notice.read || notice.id.isEmpty) {
      return;
    }
    _pendingReadIds.add(notice.id);
    _items = [for (final item in _items) item.id == notice.id ? item.copyWith(read: true) : item];
    _unread = _items.where((item) => !item.read).length;
    notifyListeners();
    unawaited(_persistCache());
    if (!LensConfig.useNetwork) {
      _pendingReadIds.remove(notice.id);
      return;
    }
    try {
      final payload = await _api.postJson('/app/notifications/${notice.id}/read');
      _pendingReadIds.remove(notice.id);
      if (payload['unread'] is int) {
        _unread = payload['unread'] as int;
        notifyListeners();
        unawaited(_persistCache());
      }
    } catch (_) {
      _pendingReadIds.remove(notice.id);
    }
  }

  Future<void> markAllRead() async {
    if (SessionStore.instance.isGuest) {
      return;
    }
    final unreadIds = _items.where((item) => !item.read).map((item) => item.id).where((id) => id.isNotEmpty);
    _pendingReadIds.addAll(unreadIds);
    _items = [for (final item in _items) item.copyWith(read: true)];
    _unread = 0;
    notifyListeners();
    unawaited(_persistCache());
    if (!LensConfig.useNetwork) {
      _pendingReadIds.clear();
      return;
    }
    try {
      await _api.postJson('/app/notifications/read-all');
      _pendingReadIds.clear();
      _unread = 0;
      notifyListeners();
      unawaited(_persistCache());
    } catch (_) {
      _pendingReadIds.clear();
    }
  }

  /// Delete every notification from the inbox (local + server).
  Future<void> clearAll() async {
    if (SessionStore.instance.isGuest) {
      return;
    }
    _pendingReadIds.clear();
    _items = [];
    _unread = 0;
    _error = null;
    notifyListeners();
    unawaited(_persistCache());
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      await _api.postJson('/app/notifications/clear-all');
      _items = [];
      _unread = 0;
      notifyListeners();
      unawaited(_persistCache());
    } catch (_) {}
  }

  /// Insert a tray push into the in-app inbox immediately, then refresh from API.
  void ingestPush({
    required String title,
    required String body,
    String? id,
    String event = 'app_notice',
  }) {
    if (SessionStore.instance.isGuest) {
      return;
    }
    final noticeId = (id != null && id.isNotEmpty)
        ? id
        : 'push-${title.hashCode}-${body.hashCode}';
    final exists = _items.any(
      (item) => item.id == noticeId || (item.title == title && item.body == body && !item.read),
    );
    if (!exists) {
      _items = [
        AppNotice(
          id: noticeId,
          title: title,
          body: body,
          event: event,
          read: false,
          createdAt: DateTime.now().toUtc().toIso8601String(),
        ),
        ..._items,
      ];
      _unread = _items.where((item) => !item.read).length;
      _loadedOnce = true;
      _error = null;
      notifyListeners();
      unawaited(_persistCache());
    }
    unawaited(refresh());
  }

  /// Open inbox: restore cache, refresh list, then clear badge after items are shown.
  Future<void> openAndClear() async {
    if (SessionStore.instance.isGuest) {
      return;
    }
    await _restoreCache();
    await refresh();
    // Keep items visible; only clear the unread badge after the list is painted.
    if (_items.isNotEmpty) {
      await Future<void>.delayed(const Duration(milliseconds: 350));
      if (!SessionStore.instance.isGuest && _items.isNotEmpty) {
        unawaited(markAllRead());
      }
    }
  }

  void _clearGuest() {
    _items = [];
    _unread = 0;
    _pendingReadIds.clear();
    _loading = false;
    _error = null;
    _loadedOnce = true;
    _cacheLoaded = false;
    notifyListeners();
  }

  void _onSessionChanged() {
    if (SessionStore.instance.isGuest) {
      _clearGuest();
      return;
    }
    _cacheLoaded = false;
    unawaited(_restoreCacheThenRefresh());
  }

  @visibleForTesting
  void reset() {
    _items = [];
    _unread = 0;
    _pendingReadIds.clear();
    _refreshInFlight = null;
    _loading = false;
    _error = null;
    _loadedOnce = false;
    _cacheLoaded = false;
    _api = ApiClient();
    notifyListeners();
  }

  static const _demo = [
    AppNotice(
      id: 'demo-1',
      title: 'Request accepted',
      body: 'Fahad Studio Light accepted your booking LN-1001.',
      event: 'booking_accepted',
      read: false,
      createdAt: '2h',
    ),
    AppNotice(
      id: 'demo-2',
      title: 'Payment received',
      body: 'EGP 1,800.00 is held in escrow for LN-1001.',
      event: 'payment',
      read: false,
      createdAt: '5h',
    ),
    AppNotice(
      id: 'demo-3',
      title: 'New offer',
      body: 'PING50 is now available on Lens.',
      event: 'offer',
      read: false,
      createdAt: '1d',
    ),
    AppNotice(
      id: 'demo-4',
      title: 'Welcome to Lens',
      body: 'Tap the heart on a creator to save them in Favorites.',
      event: 'app_notice',
      read: true,
      createdAt: '3d',
    ),
  ];
}
