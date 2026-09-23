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

  Future<Map<String, dynamic>> postForm(
    String path,
    Map<String, dynamic> body, {
    List<http.MultipartFile> files = const [],
  }) async {
    final request = http.MultipartRequest('POST', Uri.parse('$_baseUrl$path'));
    request.headers.addAll({
      'Accept': 'application/json',
      'X-Lens-Client': SessionStore.instance.email ?? LensConfig.clientEmail,
    });
    _writeFields(request, body);
    request.files.addAll(files);
    final streamed = await _client.send(request).timeout(const Duration(seconds: 60));
    return _decode(await http.Response.fromStream(streamed), path);
  }

  void _writeFields(http.MultipartRequest request, Map<String, dynamic> body, [String? prefix]) {
    body.forEach((key, value) {
      final name = prefix == null ? key : '$prefix[$key]';
      if (value == null) {
        return;
      }
      if (value is Map<String, dynamic>) {
        _writeFields(request, value, name);
        return;
      }
      if (value is List) {
        for (var index = 0; index < value.length; index++) {
          final item = value[index];
          if (item is Map<String, dynamic>) {
            _writeFields(request, item, '$name[$index]');
          } else if (item != null) {
            request.fields['$name[$index]'] = '$item';
          }
        }
        return;
      }
      request.fields[name] = value is bool ? (value ? '1' : '0') : '$value';
    });
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
