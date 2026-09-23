class HomeData {
  const HomeData({
    required this.features,
    required this.vendorTypes,
    required this.popular,
    required this.aiPrompt,
    required this.aiHelper,
    required this.searchPlaceholder,
    required this.tagline,
    required this.unreadNotifications,
    this.favoriteIds = const [],
    this.filterCatalog = const [],
    this.cities = const [],
    this.banks = const [],
    this.telecomWallets = const [],
    this.vendorRegister = const [],
  });

  factory HomeData.fromJson(Map<String, dynamic> json) {
    return HomeData(
      features: Map<String, bool>.from(
        (json['features'] as Map? ?? const {}).map((key, value) => MapEntry('$key', _asBool(value))),
      ),
      vendorTypes: (json['vendor_types'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => VendorTypeItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      popular: (json['popular'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => PopularSection.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      aiPrompt: json['ai_prompt']?.toString() ?? 'What will you create today?',
      aiHelper: json['ai_helper']?.toString() ??
          'Describe your idea and let AI find the right creatives for you.',
      searchPlaceholder: json['search_placeholder']?.toString() ??
          'Search photographers, studios, models...',
      tagline: json['tagline']?.toString() ?? 'Find. Book. Create.',
      unreadNotifications: _asInt(json['unread_notifications']),
      favoriteIds: (json['favorite_ids'] as List<dynamic>? ?? const [])
          .map((item) => item is int ? item : int.tryParse('$item') ?? 0)
          .where((id) => id > 0)
          .toList(),
      filterCatalog: (json['filter_catalog'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => FilterGroupItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
      cities: (json['cities'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
      banks: (json['banks'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
      telecomWallets: (json['telecom_wallets'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
      vendorRegister: (json['vendor_register'] as List<dynamic>? ?? const []).whereType<Map>().map(Map<String, dynamic>.from).toList(),
    );
  }

  final Map<String, bool> features;
  final List<VendorTypeItem> vendorTypes;
  final List<PopularSection> popular;
  final String aiPrompt;
  final String aiHelper;
  final String searchPlaceholder;
  final String tagline;
  final int unreadNotifications;
  final List<int> favoriteIds;
  final List<FilterGroupItem> filterCatalog;
  final List<Map<String, dynamic>> cities;
  final List<Map<String, dynamic>> banks;
  final List<Map<String, dynamic>> telecomWallets;
  final List<Map<String, dynamic>> vendorRegister;

  bool on(String key) => features[key] ?? false;

  Map<String, dynamic> get registerCatalog => {
        'vendor_types': vendorTypes.map((item) => {'slug': item.slug, 'label': item.label}).toList(),
        'cities': cities,
        'banks': banks,
        'telecom_wallets': telecomWallets,
        'vendor_register': vendorRegister,
      };
}

class FilterGroupItem {
  const FilterGroupItem({
    required this.slug,
    required this.name,
    required this.scope,
    required this.inputType,
    this.vendorTypeSlugs = const [],
    this.options = const [],
  });

  factory FilterGroupItem.fromJson(Map<String, dynamic> json) {
    return FilterGroupItem(
      slug: json['slug']?.toString() ?? '',
      name: json['name']?.toString() ?? '',
      scope: json['scope']?.toString() ?? '',
      inputType: json['input_type']?.toString() ?? '',
      vendorTypeSlugs: (json['vendor_type_slugs'] as List<dynamic>? ?? const []).map((item) => '$item').toList(),
      options: (json['options'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => FilterOptionItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }

  final String slug;
  final String name;
  final String scope;
  final String inputType;
  final List<String> vendorTypeSlugs;
  final List<FilterOptionItem> options;
}

class FilterOptionItem {
  const FilterOptionItem({required this.slug, required this.label});

  factory FilterOptionItem.fromJson(Map<String, dynamic> json) {
    return FilterOptionItem(
      slug: json['slug']?.toString() ?? '',
      label: json['label']?.toString() ?? json['name_en']?.toString() ?? '',
    );
  }

  final String slug;
  final String label;
}

class VendorTypeItem {
  const VendorTypeItem({required this.slug, required this.label});

  factory VendorTypeItem.fromJson(Map<String, dynamic> json) {
    return VendorTypeItem(
      slug: json['slug']?.toString() ?? '',
      label: json['label']?.toString() ?? json['name']?.toString() ?? '',
    );
  }

  final String slug;
  final String label;
}

class PopularSection {
  const PopularSection({required this.slug, required this.title, required this.vendors});

  factory PopularSection.fromJson(Map<String, dynamic> json) {
    return PopularSection(
      slug: json['slug']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      vendors: (json['vendors'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => VendorCard.fromJson(Map<String, dynamic>.from(item)))
          .toList(),
    );
  }

  final String slug;
  final String title;
  final List<VendorCard> vendors;
}

class VendorCard {
  const VendorCard({
    required this.id,
    required this.displayName,
    required this.vendorTypeName,
    required this.vendorType,
    required this.city,
    required this.location,
    required this.ratingAvg,
    required this.ratingCount,
    required this.badges,
    required this.coverUrl,
    required this.profilePhotoUrl,
    required this.initials,
    required this.tags,
    required this.startingFrom,
    required this.verified,
    required this.latitude,
    required this.longitude,
    this.filterTags = const [],
  });

  factory VendorCard.fromJson(Map<String, dynamic> json) {
    return VendorCard(
      id: _asInt(json['id']),
      displayName: json['display_name']?.toString() ?? '',
      vendorTypeName: json['vendor_type_name']?.toString() ?? '',
      vendorType: json['vendor_type']?.toString() ?? '',
      city: json['city']?.toString() ?? '',
      location: json['location']?.toString() ?? '',
      ratingAvg: _asDouble(json['rating_avg']),
      ratingCount: _asInt(json['rating_count']),
      badges: (json['badges'] as List<dynamic>? ?? const []).map((item) => '$item').toList(),
      coverUrl: json['cover_url']?.toString(),
      profilePhotoUrl: json['profile_photo_url']?.toString(),
      initials: json['initials']?.toString() ?? 'L',
      tags: (json['tags'] as List<dynamic>? ?? const []).map((item) => '$item').where((item) => item.isNotEmpty).toList(),
      startingFrom: json['starting_from'] == null ? null : _asDouble(json['starting_from']),
      verified: json['verified'] == true || (json['badges'] as List<dynamic>? ?? const []).map((item) => '$item').contains('verified'),
      latitude: json['latitude'] == null ? null : _asDouble(json['latitude']),
      longitude: json['longitude'] == null ? null : _asDouble(json['longitude']),
      filterTags: (json['filter_tags'] as List<dynamic>? ?? const []).map((item) => '$item').toList(),
    );
  }

  final int id;
  final String displayName;
  final String vendorTypeName;
  final String vendorType;
  final String city;
  final String location;
  final double ratingAvg;
  final int ratingCount;
  final List<String> badges;
  final String? coverUrl;
  final String? profilePhotoUrl;
  final String initials;
  final List<String> tags;
  final double? startingFrom;
  final bool verified;
  final double? latitude;
  final double? longitude;
  final List<String> filterTags;

  double get mapLatitude => latitude ?? LensCities.latitude(city);

  double get mapLongitude => longitude ?? LensCities.longitude(city);
}

class LensCities {
  const LensCities._();

  static (double, double) center(String? city) {
    return switch ((city ?? '').toLowerCase()) {
      'alexandria' => (31.2001, 29.9187),
      'giza' => (30.0131, 31.2089),
      _ => (30.0444, 31.2357),
    };
  }

  static double latitude(String? city) => center(city).$1;

  static double longitude(String? city) => center(city).$2;
}

bool _asBool(Object? value) {
  if (value == true || value == 1 || value == '1' || value == 'true') {
    return true;
  }
  return false;
}

int _asInt(Object? value) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value) ?? double.tryParse(value)?.toInt() ?? 0;
  }
  return 0;
}

double _asDouble(Object? value) {
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value) ?? 0;
  }
  return 0;
}
