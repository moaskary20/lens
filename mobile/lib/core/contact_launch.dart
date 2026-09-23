import 'package:url_launcher/url_launcher.dart';

class ContactLaunch {
  const ContactLaunch._();

  static Future<bool> open(Uri uri) async {
    try {
      return await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      return false;
    }
  }

  static Uri tel(String digits) => Uri(scheme: 'tel', path: digits);

  static Uri whatsapp(String digits) {
    var number = digits.replaceAll(RegExp(r'\D'), '');
    if (number.startsWith('0')) {
      number = '20${number.substring(1)}';
    }
    return Uri.parse('https://wa.me/$number');
  }
}
