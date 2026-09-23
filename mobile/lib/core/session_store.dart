import 'package:flutter/foundation.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';

class SessionAccount {
  const SessionAccount({
    required this.name,
    required this.email,
    required this.role,
    this.vendorId,
    this.vendorName,
  });

  factory SessionAccount.fromJson(Map<String, dynamic> json) {
    final rawRole = json['role']?.toString() ?? 'client';
    return SessionAccount(
      name: json['name']?.toString() ?? 'Lens member',
      email: json['email']?.toString() ?? '',
      role: rawRole == 'vendor' ? 'vendor' : 'client',
      vendorId: json['vendor_id'] is int ? json['vendor_id'] as int : int.tryParse('${json['vendor_id']}'),
      vendorName: json['vendor_name']?.toString(),
    );
  }

  final String name;
  final String email;
  final String role;
  final int? vendorId;
  final String? vendorName;

  bool get isVendor => role == 'vendor';
  bool get isClient => role == 'client';
}

class SessionStore extends ChangeNotifier {
  SessionStore._();

  static final SessionStore instance = SessionStore._();

  SessionAccount? _account;
  ApiClient _api = ApiClient();

  SessionAccount? get account => _account;
  bool get isGuest => _account == null;
  bool get isVendor => _account?.isVendor ?? false;
  bool get isClient => _account?.isClient ?? false;
  String? get email => _account?.email;

  Future<void> login({required String email, required String password}) async {
    if (!LensConfig.useNetwork) {
      _account = _demoAccount(email.trim(), password);
      notifyListeners();
      return;
    }
    try {
      final payload = await _api.postJson('/app/auth/login', {
        'email': email.trim(),
        'password': password,
      });
      _account = SessionAccount.fromJson(payload);
      notifyListeners();
    } on ApiException {
      _account = _tryDemo(email.trim(), password);
      if (_account == null) {
        rethrow;
      }
      notifyListeners();
    }
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required String role,
  }) async {
    if (!LensConfig.useNetwork) {
      _account = SessionAccount(name: name.trim(), email: email.trim(), role: role);
      notifyListeners();
      return;
    }
    try {
      final payload = await _api.postJson('/app/auth/register', {
        'name': name.trim(),
        'email': email.trim(),
        'password': password,
        'role': role,
      });
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
    if (email == 'client@lens.app') {
      return const SessionAccount(name: 'Sarah Bennett', email: 'client@lens.app', role: 'client');
    }
    if (email == 'vendor@lens.app') {
      return const SessionAccount(
        name: 'Fahad Photography',
        email: 'vendor@lens.app',
        role: 'vendor',
        vendorId: 1,
        vendorName: 'Fahad Studio Light',
      );
    }
    if (email == 'studio@lens.app') {
      return const SessionAccount(
        name: 'Noor Studio',
        email: 'studio@lens.app',
        role: 'vendor',
        vendorName: 'Noor Studio',
      );
    }
    return null;
  }

  @visibleForTesting
  void reset() {
    _account = null;
    _api = ApiClient();
    notifyListeners();
  }
}
