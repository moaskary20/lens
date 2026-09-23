import 'package:flutter/foundation.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';

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
}

class NotificationsStore extends ChangeNotifier {
  NotificationsStore._();

  static final NotificationsStore instance = NotificationsStore._();

  List<AppNotice> _items = [];
  int _unread = 0;
  ApiClient _api = ApiClient();

  List<AppNotice> get items => List.unmodifiable(_items);

  int get unread => _unread;

  void hydrate({int unread = 0}) {
    _unread = unread;
    if (_items.isEmpty) {
      _items = _demo;
      if (unread == 0) {
        _unread = _items.where((item) => !item.read).length;
      }
    }
    notifyListeners();
    if (LensConfig.useNetwork) {
      refresh();
    }
  }

  Future<void> refresh() async {
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      final payload = await _api.getJson('/app/notifications');
      _items = (payload['notifications'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => AppNotice.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      _unread = payload['unread'] is int ? payload['unread'] as int : _items.where((item) => !item.read).length;
      notifyListeners();
    } catch (_) {}
  }

  Future<void> markRead(AppNotice notice) async {
    if (notice.read) {
      return;
    }
    _items = [for (final item in _items) item.id == notice.id ? item.copyWith(read: true) : item];
    _unread = _items.where((item) => !item.read).length;
    notifyListeners();
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      await _api.postJson('/app/notifications/${notice.id}/read');
    } catch (_) {}
  }

  Future<void> markAllRead() async {
    _items = [for (final item in _items) item.copyWith(read: true)];
    _unread = 0;
    notifyListeners();
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      await _api.postJson('/app/notifications/read-all');
    } catch (_) {}
  }

  @visibleForTesting
  void reset() {
    _items = [];
    _unread = 0;
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
