import 'package:lens/core/models/home_data.dart';

class BookPriceOption {
  const BookPriceOption({
    required this.key,
    required this.label,
    required this.price,
    this.durationHours,
  });

  factory BookPriceOption.fromJson(Map<String, dynamic> json) {
    return BookPriceOption(
      key: json['key']?.toString() ?? json['label']?.toString() ?? 'custom',
      label: json['label']?.toString() ?? 'Package',
      price: json['price'] is num ? (json['price'] as num).toDouble() : double.tryParse('${json['price']}') ?? 0,
      durationHours: json['duration_hours'] is num ? (json['duration_hours'] as num).toInt() : int.tryParse('${json['duration_hours']}'),
    );
  }

  final String key;
  final String label;
  final double price;
  final int? durationHours;
}

class PickedMapLocation {
  const PickedMapLocation({
    required this.label,
    required this.latitude,
    required this.longitude,
  });

  final String label;
  final double latitude;
  final double longitude;
}

class BookDraft {
  BookDraft({
    required this.vendor,
    required this.home,
    required this.dateLabel,
    required this.time,
    this.scheduledAt,
    this.packageName = '',
    this.packageKey = '',
    this.packageDetails = '',
    this.sessionPrice = 0,
    this.projectName = '',
    this.projectType = '',
    this.location = '',
    this.latitude,
    this.longitude,
    this.brief = '',
    this.notes = '',
    Map<String, String>? details,
    List<String>? images,
    this.paymentMethod = 'card',
    this.promoCode,
    this.payment,
  }) : details = details ?? <String, String>{},
       images = images ?? <String>[];

  final VendorCard vendor;
  final HomeData home;
  String dateLabel;
  String time;
  DateTime? scheduledAt;
  String packageName;
  String packageKey;
  String packageDetails;
  double sessionPrice;
  String projectName;
  String projectType;
  String location;
  double? latitude;
  double? longitude;
  String brief;
  String notes;
  Map<String, String> details;
  List<String> images;
  String paymentMethod;
  String? promoCode;
  BookPaymentDetails? payment;

  bool get hasPaymentDetails => payment != null && payment!.method == paymentMethod && payment!.isComplete;

  String get durationLabel {
    final hours = RegExp(r'(\d+)\s*Hours?', caseSensitive: false).firstMatch(packageDetails);
    if (hours != null) {
      return '${hours.group(1)} Hours';
    }
    final part = packageDetails.split('•').first.trim();
    if (part.isNotEmpty) {
      return part;
    }
    if (packageName.isNotEmpty) {
      return packageName;
    }
    return 'Session';
  }
}

class BookPaymentDetails {
  const BookPaymentDetails({
    required this.method,
    this.cardHolder = '',
    this.cardBrand = '',
    this.cardLast4 = '',
    this.cardExpiry = '',
    this.walletPhone = '',
    this.walletTelecom = '',
    this.paypalEmail = '',
    this.paypalName = '',
  });

  final String method;
  final String cardHolder;
  final String cardBrand;
  final String cardLast4;
  final String cardExpiry;
  final String walletPhone;
  final String walletTelecom;
  final String paypalEmail;
  final String paypalName;

  bool get isComplete => switch (method) {
        'card' => cardHolder.isNotEmpty && cardLast4.length == 4 && cardExpiry.isNotEmpty,
        'wallet' => walletPhone.length == 11 && walletTelecom.isNotEmpty,
        'paypal' => paypalEmail.contains('@') && paypalName.isNotEmpty,
        _ => false,
      };

  String get summary => switch (method) {
        'card' => [
            if (cardBrand.isNotEmpty) cardBrand,
            if (cardLast4.isNotEmpty) '•••• $cardLast4',
            if (cardExpiry.isNotEmpty) cardExpiry,
          ].join('  •  '),
        'wallet' => [
            if (walletTelecom.isNotEmpty) walletTelecom,
            if (walletPhone.isNotEmpty) walletPhone,
          ].join('  •  '),
        'paypal' => paypalEmail,
        _ => '',
      };

  Map<String, String> toApi() {
    return switch (method) {
      'card' => {
          'method': 'card',
          'card_holder': cardHolder,
          'card_brand': cardBrand,
          'card_last4': cardLast4,
          'card_expiry': cardExpiry,
        },
      'wallet' => {
          'method': 'wallet',
          'wallet_phone': walletPhone,
          'wallet_telecom': walletTelecom,
        },
      'paypal' => {
          'method': 'paypal',
          'paypal_email': paypalEmail,
          'paypal_name': paypalName,
        },
      _ => {'method': method},
    };
  }
}
