class EgyptPhone {
  const EgyptPhone._();

  static final pattern = RegExp(r'^01[0125][0-9]{8}$');
  static const message = 'Use an Egyptian mobile number of 11 digits starting with 010, 011, 012, or 015.';

  static String digits(String raw) => raw.replaceAll(RegExp(r'\D'), '');

  static bool isValid(String raw, {bool required = false}) {
    final value = digits(raw);
    if (value.isEmpty) {
      return !required;
    }
    return pattern.hasMatch(value);
  }
}
