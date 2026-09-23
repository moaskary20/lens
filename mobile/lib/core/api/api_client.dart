import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';

class ApiClient {
  ApiClient({http.Client? client, String? baseUrl})
      : _client = client ?? http.Client(),
        _baseUrl = baseUrl ?? LensConfig.apiBaseUrl;

  final http.Client _client;
  final String _baseUrl;

  Future<Map<String, dynamic>> getJson(String path) async {
    final response = await _client
        .get(Uri.parse('$_baseUrl$path'), headers: _headers)
        .timeout(const Duration(seconds: 8));
    return _decode(response, path);
  }

  Future<Map<String, dynamic>> postJson(String path, [Map<String, dynamic>? body]) async {
    final response = await _client
        .post(
          Uri.parse('$_baseUrl$path'),
          headers: _headers,
          body: jsonEncode(body ?? const <String, dynamic>{}),
        )
        .timeout(const Duration(seconds: 8));
    return _decode(response, path);
  }

  Future<Map<String, dynamic>> deleteJson(String path) async {
    final response = await _client
        .delete(Uri.parse('$_baseUrl$path'), headers: _headers)
        .timeout(const Duration(seconds: 8));
    return _decode(response, path);
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Lens-Client': SessionStore.instance.email ?? LensConfig.clientEmail,
      };

  Map<String, dynamic> _decode(http.Response response, String path) {
    if (response.statusCode < 200 || response.statusCode >= 300) {
      var message = 'Request failed (${response.statusCode}) for $path';
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map && decoded['message'] != null) {
          message = decoded['message'].toString();
        }
      } catch (_) {}
      throw ApiException(message);
    }

    if (response.body.isEmpty) {
      return const {};
    }

    final decoded = jsonDecode(response.body);
    if (decoded is! Map) {
      throw const ApiException('Unexpected JSON payload.');
    }

    return Map<String, dynamic>.from(decoded);
  }
}

class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}
