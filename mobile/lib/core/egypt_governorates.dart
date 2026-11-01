/// English labels for Egyptian governorates, with Arabic/device aliases.
class EgyptGovernorates {
  const EgyptGovernorates._();

  static const english = <String>[
    'Cairo',
    'Giza',
    'Alexandria',
    'Qalyubia',
    'Dakahlia',
    'Sharqia',
    'Gharbia',
    'Monufia',
    'Beheira',
    'Kafr El Sheikh',
    'Damietta',
    'Port Said',
    'Ismailia',
    'Suez',
    'North Sinai',
    'South Sinai',
    'Fayoum',
    'Beni Suef',
    'Minya',
    'Assiut',
    'Sohag',
    'Qena',
    'Luxor',
    'Aswan',
    'Red Sea',
    'New Valley',
    'Matrouh',
  ];

  static const _aliases = <String, String>{
    'القاهرة': 'Cairo',
    'cairo': 'Cairo',
    'الجيزة': 'Giza',
    'giza': 'Giza',
    'el giza': 'Giza',
    'الإسكندرية': 'Alexandria',
    'الاسكندرية': 'Alexandria',
    'alexandria': 'Alexandria',
    'alex': 'Alexandria',
    'القليوبية': 'Qalyubia',
    'qalyubia': 'Qalyubia',
    'qalyubiyah': 'Qalyubia',
    'الدقهلية': 'Dakahlia',
    'dakahlia': 'Dakahlia',
    'daqahlia': 'Dakahlia',
    'الشرقية': 'Sharqia',
    'sharqia': 'Sharqia',
    'sharkia': 'Sharqia',
    'الغربية': 'Gharbia',
    'gharbia': 'Gharbia',
    'المنوفية': 'Monufia',
    'monufia': 'Monufia',
    'menoufia': 'Monufia',
    'البحيرة': 'Beheira',
    'beheira': 'Beheira',
    'beheira governorate': 'Beheira',
    'كفر الشيخ': 'Kafr El Sheikh',
    'kafr el sheikh': 'Kafr El Sheikh',
    'kafr ash shaykh': 'Kafr El Sheikh',
    'دمياط': 'Damietta',
    'damietta': 'Damietta',
    'بورسعيد': 'Port Said',
    'port said': 'Port Said',
    'الإسماعيلية': 'Ismailia',
    'الاسماعيلية': 'Ismailia',
    'ismailia': 'Ismailia',
    'السويس': 'Suez',
    'suez': 'Suez',
    'شمال سيناء': 'North Sinai',
    'north sinai': 'North Sinai',
    'جنوب سيناء': 'South Sinai',
    'south sinai': 'South Sinai',
    'الفيوم': 'Fayoum',
    'fayoum': 'Fayoum',
    'faiyum': 'Fayoum',
    'بني سويف': 'Beni Suef',
    'beni suef': 'Beni Suef',
    'المنيا': 'Minya',
    'minya': 'Minya',
    'أسيوط': 'Assiut',
    'اسيوط': 'Assiut',
    'assiut': 'Assiut',
    'asyut': 'Assiut',
    'سوهاج': 'Sohag',
    'sohag': 'Sohag',
    'قنا': 'Qena',
    'qena': 'Qena',
    'الأقصر': 'Luxor',
    'الاقصر': 'Luxor',
    'luxor': 'Luxor',
    'أسوان': 'Aswan',
    'اسوان': 'Aswan',
    'aswan': 'Aswan',
    'البحر الأحمر': 'Red Sea',
    'البحر الاحمر': 'Red Sea',
    'red sea': 'Red Sea',
    'الوادي الجديد': 'New Valley',
    'new valley': 'New Valley',
    'مطروح': 'Matrouh',
    'matrouh': 'Matrouh',
    'matruh': 'Matrouh',
  };

  static String normalize(String value) => value
      .replaceAll(RegExp(r'\s+Governorate$', caseSensitive: false), '')
      .replaceAll(RegExp(r'\s+Province$', caseSensitive: false), '')
      .replaceAll(RegExp(r'\s+محافظة$', caseSensitive: false), '')
      .trim()
      .toLowerCase();

  /// Returns the English governorate name when possible.
  static String toEnglish(String raw) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) {
      return trimmed;
    }
    final key = normalize(trimmed);
    final mapped = _aliases[key] ?? _aliases[trimmed];
    if (mapped != null) {
      return mapped;
    }
    for (final name in english) {
      if (normalize(name) == key) {
        return name;
      }
    }
    return trimmed
        .replaceFirst(RegExp(r'\s+Governorate$', caseSensitive: false), '')
        .replaceFirst(RegExp(r'\s+Province$', caseSensitive: false), '')
        .trim();
  }
}
