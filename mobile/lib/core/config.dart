import 'package:flutter/foundation.dart';

class LensConfig {
  const LensConfig._();

  static const String _fromEnv = String.fromEnvironment('API_BASE_URL');
  static const String googleMapsApiKey = 'AIzaSyDijsR3eTz9oM39y8V0fejOh5LW-IAfPRY';
  static const String clientEmail = 'client@lens.app';
  static bool useNetwork = true;

  /// Chrome/web and desktop → localhost. Android emulator → 10.0.2.2.
  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) {
      return _fromEnv;
    }
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api';
    }
    return 'http://10.0.2.2:8000/api';
  }
}
