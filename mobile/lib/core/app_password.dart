class AppPassword {
  const AppPassword._();

  static const minLength = 8;
  static const message =
      'Password must be at least 8 characters and include letters, numbers, and a symbol.';
  static const hint = 'Password *';
  static const hintDetail = 'Use letters, numbers, and a symbol';

  static final _letter = RegExp(r'[A-Za-z]');
  static final _digit = RegExp(r'[0-9]');
  static final _symbol = RegExp(r'[^A-Za-z0-9]');

  static bool isStrong(String value) {
    if (value.length < minLength) {
      return false;
    }
    return _letter.hasMatch(value) && _digit.hasMatch(value) && _symbol.hasMatch(value);
  }

  static String? validate(String value, {bool required = true}) {
    if (value.isEmpty) {
      return required ? message : null;
    }
    return isStrong(value) ? null : message;
  }
}
