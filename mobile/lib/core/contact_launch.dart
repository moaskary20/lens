import 'package:url_launcher/url_launcher.dart';

class ContactLaunch {
  const ContactLaunch._();

  static String digits(String? raw) {
    final value = (raw ?? '').replaceAll(RegExp(r'\D'), '');
    if (value.isEmpty || value.toLowerCase() == 'null') {
      return '';
    }
    return value;
  }

  static bool hasNumber(String? raw) => digits(raw).isNotEmpty;

  /// Egyptian mobiles are stored as 01XXXXXXXXX. WhatsApp needs country code 20.
  static String internationalEgypt(String? raw) {
    var number = digits(raw);
    if (number.isEmpty) {
      return '';
    }
    if (number.startsWith('00')) {
      number = number.substring(2);
    }
    if (number.startsWith('0') && number.length == 11) {
      number = '20${number.substring(1)}';
    } else if (!number.startsWith('20') && number.length == 10) {
      number = '20$number';
    }
    return number;
  }

  static Future<bool> open(Uri uri) async {
    try {
      if (await canLaunchUrl(uri)) {
        return launchUrl(uri, mode: LaunchMode.externalApplication);
      }
      return launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      return false;
    }
  }

  static Uri tel(String raw) {
    final number = digits(raw);
    return Uri.parse('tel:$number');
  }

  static Uri whatsapp(String raw) {
    final number = internationalEgypt(raw);
    return Uri.parse('https://wa.me/$number');
  }
}
