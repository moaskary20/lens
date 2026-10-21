class LensConfig {
  const LensConfig._();

  static const String _fromEnv = String.fromEnvironment('API_BASE_URL');
  static const String googleMapsApiKey =
      'AIzaSyDijsR3eTz9oM39y8V0fejOh5LW-IAfPRY';
  static const String clientEmail = 'client@lens.app';
  static bool useNetwork = true;

  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) {
      return _fromEnv;
    }
    return 'https://lensapp.org/api';
  }
}
