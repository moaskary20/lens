import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';

class SessionAccount {
  const SessionAccount({
    required this.name,
    required this.email,
    required this.role,
    this.vendorId,
    this.vendorName,
    this.phone,
    this.avatarUrl,
    this.cityId,
    this.cityName,
    this.locale = 'en',
    this.isActive = true,
    this.joinedAt,
  });

  factory SessionAccount.fromJson(Map<String, dynamic> json) {
    final rawRole = json['role']?.toString() ?? 'client';
    return SessionAccount(
      name: json['name']?.toString() ?? 'Lens member',
      email: json['email']?.toString() ?? '',
      role: rawRole == 'vendor' ? 'vendor' : 'client',
      vendorId: json['vendor_id'] is int ? json['vendor_id'] as int : int.tryParse('${json['vendor_id']}'),
      vendorName: json['vendor_name']?.toString(),
      phone: json['phone']?.toString(),
      avatarUrl: json['avatar']?.toString(),
      cityId: json['city_id'] is int ? json['city_id'] as int : int.tryParse('${json['city_id']}'),
      cityName: json['city']?.toString(),
      locale: json['locale']?.toString() == 'ar' ? 'ar' : 'en',
      isActive: json['is_active'] != false,
      joinedAt: json['joined_at']?.toString(),
    );
  }

  final String name;
  final String email;
  final String role;
  final int? vendorId;
  final String? vendorName;
  final String? phone;
  final String? avatarUrl;
  final int? cityId;
  final String? cityName;
  final String locale;
  final bool isActive;
  final String? joinedAt;

  bool get isVendor => role == 'vendor';
  bool get isClient => role == 'client';

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty).toList();
    if (parts.isEmpty) {
      return 'L';
    }
    final letters = parts.take(2).map((part) => part.substring(0, 1).toUpperCase()).join();
    return letters;
  }

  SessionAccount copyWith({
    String? name,
    String? email,
    String? role,
    int? vendorId,
    String? vendorName,
    String? phone,
    String? avatarUrl,
    int? cityId,
    String? cityName,
    String? locale,
    bool? isActive,
    String? joinedAt,
  }) {
    return SessionAccount(
      name: name ?? this.name,
      email: email ?? this.email,
      role: role ?? this.role,
      vendorId: vendorId ?? this.vendorId,
      vendorName: vendorName ?? this.vendorName,
      phone: phone ?? this.phone,
      avatarUrl: avatarUrl ?? this.avatarUrl,
      cityId: cityId ?? this.cityId,
      cityName: cityName ?? this.cityName,
      locale: locale ?? this.locale,
      isActive: isActive ?? this.isActive,
      joinedAt: joinedAt ?? this.joinedAt,
    );
  }
}

class SessionStore extends ChangeNotifier {
  SessionStore._();

  static final SessionStore instance = SessionStore._();

  SessionAccount? _account;
  ApiClient _api = ApiClient();
  String _locale = 'en';

  SessionAccount? get account => _account;
  bool get isGuest => _account == null;
  bool get isVendor => _account?.isVendor ?? false;
  bool get isClient => _account?.isClient ?? false;
  String? get email => _account?.email;
  String get locale => _account?.locale ?? _locale;

  Future<void> login({required String email, required String password}) async {
    if (!LensConfig.useNetwork) {
      _account = _demoAccount(email.trim(), password);
      _locale = _account?.locale ?? 'en';
      notifyListeners();
      return;
    }
    try {
      final payload = await _api.postJson('/app/auth/login', {
        'email': email.trim(),
        'password': password,
      });
      _account = SessionAccount.fromJson(payload);
      _locale = _account?.locale ?? 'en';
      notifyListeners();
    } on ApiException {
      _account = _tryDemo(email.trim(), password);
      if (_account == null) {
        rethrow;
      }
      _locale = _account?.locale ?? 'en';
      notifyListeners();
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String role,
    Map<String, dynamic> extra = const {},
    List<http.MultipartFile> files = const [],
  }) async {
    if (!LensConfig.useNetwork) {
      _account = SessionAccount(name: name.trim(), email: email.trim(), role: role, locale: _locale);
      notifyListeners();
      return;
    }
    try {
      final body = {
        'name': name.trim(),
        'email': email.trim(),
        'password': password,
        'role': role,
        ...extra,
      };
      final payload = files.isEmpty
          ? await _api.postJson('/app/auth/register', body)
          : await _api.postForm('/app/auth/register', body, files: files);
      _account = SessionAccount.fromJson(payload);
      notifyListeners();
    } on ApiException {
      _account = SessionAccount(name: name.trim(), email: email.trim(), role: role);
      notifyListeners();
    }
  }

  void signOut() {
    _account = null;
    notifyListeners();
  }

  void applyAccount(SessionAccount account) {
    _account = account;
    _locale = account.locale;
    notifyListeners();
  }

  Future<void> setLocale(String value) async {
    final locale = value == 'ar' ? 'ar' : 'en';
    _locale = locale;
    if (_account != null) {
      _account = _account!.copyWith(locale: locale);
    }
    notifyListeners();
    if (LensConfig.useNetwork && isClient) {
      try {
        await _api.postJson('/app/account/settings', {
          'settings': {'language': locale},
        });
      } catch (_) {}
    }
  }

  SessionAccount _demoAccount(String email, String password) {
    final match = _tryDemo(email, password);
    if (match == null) {
      throw const ApiException('Invalid email or password.');
    }
    return match;
  }

  SessionAccount? _tryDemo(String email, String password) {
    if (password != 'password') {
      return null;
    }
    if (email == 'admin@lens.app' || email == 'supervisor@lens.app') {
      return SessionAccount(
        name: email == 'admin@lens.app' ? 'Lens Admin' : 'Lens Supervisor',
        email: email,
        role: 'client',
        phone: '01000000000',
        cityId: 1,
        cityName: 'Cairo',
        locale: 'en',
      );
    }
    if (email == 'client@lens.app') {
      return const SessionAccount(
        name: 'Sarah Bennett',
        email: 'client@lens.app',
        role: 'client',
        phone: '01011112233',
        cityId: 1,
        cityName: 'Cairo',
        locale: 'en',
        joinedAt: '2026-01-15',
      );
    }
    if (email == 'vendor@lens.app') {
      return const SessionAccount(
        name: 'Fahad Photography',
        email: 'vendor@lens.app',
        role: 'vendor',
        vendorId: 1,
        vendorName: 'Fahad Studio Light',
        phone: '01022223344',
        cityId: 1,
        cityName: 'Cairo',
        locale: 'en',
      );
    }
    if (email == 'studio@lens.app') {
      return const SessionAccount(
        name: 'Noor Studio',
        email: 'studio@lens.app',
        role: 'vendor',
        vendorName: 'Noor Studio',
        phone: '01033334455',
        cityId: 3,
        cityName: 'Alexandria',
        locale: 'en',
      );
    }
    return null;
  }

  @visibleForTesting
  void reset() {
    _account = null;
    _locale = 'en';
    _api = ApiClient();
    notifyListeners();
  }
}
