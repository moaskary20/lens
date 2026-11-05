import 'dart:convert';

import 'package:lens/core/demo_bootstrap.dart';
import 'package:shared_preferences/shared_preferences.dart';

class BootstrapCache {
  const BootstrapCache._();

  static const _typesKey = 'lens_bootstrap_vendor_types_v1';

  static List<Map<String, dynamic>> demoTypes() {
    return (DemoBootstrap.payload()['vendor_types'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  static Future<void> saveVendorTypes(Object? types) async {
    if (types is! List || types.isEmpty) {
      return;
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_typesKey, jsonEncode(types));
  }

  static Future<List<Map<String, dynamic>>?> loadVendorTypes() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_typesKey);
    if (raw == null || raw.isEmpty) {
      return null;
    }
    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List || decoded.isEmpty) {
        return null;
      }
      return decoded
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    } catch (_) {
      return null;
    }
  }

  static Set<String> typeSlugs(Object? types) {
    if (types is! List) {
      return {};
    }
    return {
      for (final item in types)
        if (item is Map && '${item['slug'] ?? ''}'.trim().isNotEmpty)
          '${item['slug']}'.trim(),
    };
  }

  /// Prefer the richest non-empty vendor_types list (remote > cache > demo).
  static List<Map<String, dynamic>> bestTypes({
    Object? remote,
    List<Map<String, dynamic>>? cached,
  }) {
    final remoteList = (remote is List ? remote : const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .where((item) => '${item['slug'] ?? ''}'.trim().isNotEmpty)
        .toList();
    final cachedList = cached ?? const <Map<String, dynamic>>[];
    final demo = demoTypes();

    List<Map<String, dynamic>> pick(List<Map<String, dynamic>> a, List<Map<String, dynamic>> b) {
      if (a.isEmpty) {
        return b;
      }
      if (b.isEmpty) {
        return a;
      }
      if (a.length != b.length) {
        return a.length >= b.length ? a : b;
      }
      return a;
    }

    return pick(remoteList, pick(cachedList, demo));
  }

  /// Keep categories available on first paint even when remote bootstrap is slow.
  static Map<String, dynamic> withFastTypes(
    Map<String, dynamic> bootstrap, {
    List<Map<String, dynamic>>? cached,
  }) {
    final next = Map<String, dynamic>.from(bootstrap);
    next['vendor_types'] = bestTypes(
      remote: bootstrap['vendor_types'],
      cached: cached,
    );
    return next;
  }
}
