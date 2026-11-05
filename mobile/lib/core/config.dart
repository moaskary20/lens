class LensConfig {
  const LensConfig._();

  static const String _fromEnv = String.fromEnvironment('API_BASE_URL');
  static const String googleMapsApiKey =
      'AIzaSyDijsR3eTz9oM39y8V0fejOh5LW-IAfPRY';
  /// Web OAuth client from Firebase (used as Google Sign-In serverClientId).
  static const String googleWebClientId =
      '165894637736-lil7v98s08ndt4munuj7885iv8nmsos1.apps.googleusercontent.com';
  static const String firebaseProjectId = 'lens-fa2b5';
  static const String clientEmail = 'client@lens.app';
  static bool useNetwork = true;

  static String get apiBaseUrl {
    if (_fromEnv.isNotEmpty) {
      return _fromEnv;
    }
    return 'https://lensapp.org/api';
  }
}
