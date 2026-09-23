class CatalogOption {
  const CatalogOption({required this.id, required this.label});

  final String id;
  final String label;
}

class RegisterFieldSpec {
  const RegisterFieldSpec({
    required this.key,
    required this.type,
    required this.label,
    this.placeholder,
    this.helper,
    this.prefix,
    this.options = const [],
  });

  factory RegisterFieldSpec.fromJson(Map<String, dynamic> json) {
    return RegisterFieldSpec(
      key: json['key']?.toString() ?? '',
      type: json['type']?.toString() ?? 'text',
      label: json['label']?.toString() ?? '',
      placeholder: json['placeholder']?.toString(),
      helper: json['helper']?.toString(),
      prefix: json['prefix']?.toString(),
      options: RegisterCatalog.optionsOf(json['options']),
    );
  }

  final String key;
  final String type;
  final String label;
  final String? placeholder;
  final String? helper;
  final String? prefix;
  final List<CatalogOption> options;

  bool get isList => type == 'tags' || type == 'multi';
}

class PricingModelSpec {
  const PricingModelSpec({
    required this.id,
    required this.name,
    this.fields = const [],
  });

  factory PricingModelSpec.fromJson(Map<String, dynamic> json) {
    return PricingModelSpec(
      id: json['id']?.toString() ?? json['slug']?.toString() ?? '',
      name: json['name']?.toString() ?? json['slug']?.toString() ?? 'Pricing model',
      fields: (json['fields'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => RegisterFieldSpec.fromJson(Map<String, dynamic>.from(item)))
          .where((item) => item.key.isNotEmpty)
          .toList(),
    );
  }

  final String id;
  final String name;
  final List<RegisterFieldSpec> fields;
}

class VendorTypeSpec {
  const VendorTypeSpec({
    required this.slug,
    required this.label,
    required this.profileTitle,
    required this.profileDescription,
    this.pricingModelName,
    this.fields = const [],
    this.pricing = const [],
    this.pricingModels = const [],
  });

  factory VendorTypeSpec.fromJson(Map<String, dynamic> json) {
    final pricing = (json['pricing'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => RegisterFieldSpec.fromJson(Map<String, dynamic>.from(item)))
        .where((item) => item.key.isNotEmpty)
        .toList();
    final models = (json['pricing_models'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => PricingModelSpec.fromJson(Map<String, dynamic>.from(item)))
        .where((item) => item.id.isNotEmpty)
        .toList();
    return VendorTypeSpec(
      slug: json['slug']?.toString() ?? '',
      label: json['label']?.toString() ?? json['name']?.toString() ?? '',
      profileTitle: json['profile_title']?.toString() ?? 'Type profile',
      profileDescription: json['profile_description']?.toString() ?? '',
      pricingModelName: json['pricing_model']?.toString(),
      fields: (json['fields'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => RegisterFieldSpec.fromJson(Map<String, dynamic>.from(item)))
          .where((item) => item.key.isNotEmpty)
          .toList(),
      pricing: pricing,
      pricingModels: models.isNotEmpty
          ? models
          : [
              if (pricing.isNotEmpty)
                PricingModelSpec(
                  id: json['pricing_model_id']?.toString() ?? 'default',
                  name: json['pricing_model']?.toString() ?? 'Pricing model',
                  fields: pricing,
                ),
            ],
    );
  }

  final String slug;
  final String label;
  final String profileTitle;
  final String profileDescription;
  final String? pricingModelName;
  final List<RegisterFieldSpec> fields;
  final List<RegisterFieldSpec> pricing;
  final List<PricingModelSpec> pricingModels;

  List<PricingModelSpec> get resolvedModels {
    if (pricingModels.isNotEmpty) {
      return pricingModels;
    }
    if (pricing.isEmpty) {
      return const [];
    }
    return [
      PricingModelSpec(id: slug, name: pricingModelName ?? 'Assigned model', fields: pricing),
    ];
  }
}

class RegisterCatalog {
  const RegisterCatalog({
    this.cities = const [],
    this.banks = const [],
    this.telecoms = const [],
    this.vendorTypes = const [],
    this.typeSpecs = const [],
  });

  factory RegisterCatalog.fromBootstrap(Map<String, dynamic> json) {
    return RegisterCatalog(
      cities: optionsOf(json['cities'], idKey: 'id', labelKey: 'name'),
      banks: optionsOf(json['banks']),
      telecoms: optionsOf(json['telecom_wallets']),
      vendorTypes: optionsOf(json['vendor_types'], idKey: 'slug', labelKey: 'label', fallbackLabel: 'name'),
      typeSpecs: (json['vendor_register'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => VendorTypeSpec.fromJson(Map<String, dynamic>.from(item)))
          .where((item) => item.slug.isNotEmpty)
          .toList(),
    );
  }

  final List<CatalogOption> cities;
  final List<CatalogOption> banks;
  final List<CatalogOption> telecoms;
  final List<CatalogOption> vendorTypes;
  final List<VendorTypeSpec> typeSpecs;

  List<CatalogOption> get cityChoices => cities.isNotEmpty ? cities : _fallbackCities;
  List<CatalogOption> get bankChoices => banks.isNotEmpty ? banks : _fallbackBanks;
  List<CatalogOption> get telecomChoices => telecoms.isNotEmpty ? telecoms : _fallbackTelecoms;
  List<CatalogOption> get typeChoices {
    if (typeSpecs.isNotEmpty) {
      return typeSpecs.map((item) => CatalogOption(id: item.slug, label: item.label)).toList();
    }
    return vendorTypes.isNotEmpty ? vendorTypes : _fallbackTypes;
  }

  VendorTypeSpec specFor(String? slug) {
    for (final item in typeSpecs) {
      if (item.slug == slug) {
        return item;
      }
    }
    return _fallbackSpec(slug);
  }

  static List<CatalogOption> optionsOf(
    Object? raw, {
    String idKey = 'id',
    String labelKey = 'label',
    String? fallbackLabel,
  }) {
    return (raw as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) {
          final map = Map<String, dynamic>.from(item);
          final id = map[idKey]?.toString() ?? '';
          final label = map[labelKey]?.toString() ?? map[fallbackLabel]?.toString() ?? id;
          return CatalogOption(id: id, label: label);
        })
        .where((item) => item.id.isNotEmpty)
        .toList();
  }

  static VendorTypeSpec _fallbackSpec(String? slug) {
    return switch (slug) {
      'videographer' => const VendorTypeSpec(
          slug: 'videographer',
          label: 'Videographer',
          profileTitle: 'Videographer profile',
          profileDescription: 'Same video quality and gear options clients use on the mobile filter screens.',
          fields: [
            RegisterFieldSpec(key: 'filter.video_quality', type: 'multi', label: 'Video quality & formats', options: _videoQuality),
            RegisterFieldSpec(key: 'filter.video_gear', type: 'multi', label: 'Video gear', options: _videoGear),
            RegisterFieldSpec(key: 'delivery_formats', type: 'multi', label: 'Supported delivery formats', options: _deliveryFormats),
          ],
          pricing: _halfFullPricing,
        ),
      'reels' => const VendorTypeSpec(
          slug: 'reels',
          label: 'Mobile Reels Creator',
          profileTitle: 'Mobile Reels Creator profile',
          profileDescription: 'Same mobile-reel filter options plus sample links.',
          fields: [
            RegisterFieldSpec(key: 'filter.video_quality', type: 'multi', label: 'Video quality & formats', options: _videoQuality),
            RegisterFieldSpec(key: 'filter.video_gear', type: 'multi', label: 'Video gear', options: _videoGear),
            RegisterFieldSpec(key: 'filter.reels', type: 'multi', label: 'Mobile reel creators', options: _reelCreators),
            RegisterFieldSpec(key: 'extras.tiktok_url', type: 'url', label: 'TikTok sample link'),
            RegisterFieldSpec(key: 'extras.instagram_url', type: 'url', label: 'Instagram / Reels sample link'),
            RegisterFieldSpec(key: 'extras.standard_session_hours', type: 'number', label: 'Standard session length (hours)'),
          ],
          pricing: _halfFullPricing,
        ),
      'studio' => const VendorTypeSpec(
          slug: 'studio',
          label: 'Studio',
          profileTitle: 'Studio profile',
          profileDescription: 'Same studio type, size, area, and equipment options as Filter Studios.',
          fields: [
            RegisterFieldSpec(key: 'filter.studio_type', type: 'multi', label: 'Studio Type', options: _studioTypes),
            RegisterFieldSpec(key: 'filter.studio_areas', type: 'multi', label: 'Studio areas', options: _studioAreas),
            RegisterFieldSpec(key: 'filter.studio_features', type: 'multi', label: 'Features & Equipment', options: _studioFeatures),
            RegisterFieldSpec(key: 'filter.studio_size', type: 'multi', label: 'Studio Size (m²)', options: _studioSizes),
          ],
          pricing: [
            RegisterFieldSpec(key: 'hourly_price', type: 'number', label: 'Price per hour per location', prefix: 'EGP'),
          ],
        ),
      'model' => const VendorTypeSpec(
          slug: 'model',
          label: 'Model',
          profileTitle: 'Model profile',
          profileDescription: 'Same gender, category, age, height, and size options as Filter Models.',
          fields: [
            RegisterFieldSpec(key: 'filter.model_identity', type: 'multi', label: 'Gender', options: _modelGenders),
            RegisterFieldSpec(key: 'filter.model_category', type: 'multi', label: 'Category', options: _modelCategories),
            RegisterFieldSpec(key: 'filter.model_age', type: 'multi', label: 'Age Range', options: _modelAges),
            RegisterFieldSpec(key: 'filter.model_height', type: 'multi', label: 'Height (cm)', options: _modelHeights),
            RegisterFieldSpec(key: 'filter.model_tops', type: 'multi', label: 'T-Shirt / Tops', options: _modelTops),
            RegisterFieldSpec(key: 'filter.model_pants', type: 'multi', label: 'Pants / Bottoms (Waist)', options: _modelPants),
            RegisterFieldSpec(key: 'filter.model_shoes', type: 'multi', label: 'Shoes (EU)', options: _modelShoes),
            RegisterFieldSpec(key: 'extras.hair_color', type: 'text', label: 'Hair color'),
            RegisterFieldSpec(key: 'extras.eye_color', type: 'text', label: 'Eye color'),
          ],
          pricing: _halfFullPricing,
        ),
      'ugc' => const VendorTypeSpec(
          slug: 'ugc',
          label: 'UGC Creator',
          profileTitle: 'UGC Creator profile',
          profileDescription: 'Same niche, city, accent, and content options as Filter UGC Creators.',
          fields: [
            RegisterFieldSpec(key: 'filter.ugc_niche', type: 'multi', label: 'Category / Niche', options: _ugcNiches),
            RegisterFieldSpec(key: 'filter.ugc_cities', type: 'multi', label: 'City (Egypt)', options: _ugcCities),
            RegisterFieldSpec(key: 'filter.ugc_content', type: 'multi', label: 'Content Type', options: _ugcContent),
            RegisterFieldSpec(key: 'filter.ugc_gender', type: 'multi', label: 'Gender', options: _modelGenders),
          ],
          pricing: [
            RegisterFieldSpec(key: 'per_video_price', type: 'number', label: 'Price per video', prefix: 'EGP'),
            RegisterFieldSpec(key: 'turnaround_hours', type: 'number', label: 'Post-production turnaround (hours)'),
          ],
        ),
      'food_stylist' => const VendorTypeSpec(
          slug: 'food_stylist',
          label: 'Food Stylist',
          profileTitle: 'Food Stylist profile',
          profileDescription: 'Same expertise, cuisine, city, and content options as Filter Food Stylists.',
          fields: [
            RegisterFieldSpec(key: 'filter.food_expertise', type: 'multi', label: 'Category / Expertise', options: _foodExpertise),
            RegisterFieldSpec(key: 'filter.food_cuisine', type: 'multi', label: 'Cuisine / Food Type', options: _foodCuisine),
            RegisterFieldSpec(key: 'filter.food_cities', type: 'multi', label: 'Egyptian Cities', options: _ugcCities),
            RegisterFieldSpec(key: 'filter.food_content', type: 'multi', label: 'Content Type', options: _ugcContent),
            RegisterFieldSpec(key: 'extras.addon_notes', type: 'textarea', label: 'Add-on notes'),
          ],
          pricing: _halfFullPricing,
        ),
      _ => const VendorTypeSpec(
          slug: 'photographer',
          label: 'Photographer',
          profileTitle: 'Photographer profile',
          profileDescription: 'Same camera, lens, and lighting options clients use on the mobile filter screens.',
          fields: [
            RegisterFieldSpec(key: 'filter.camera', type: 'multi', label: 'Camera type', options: _cameraTypes),
            RegisterFieldSpec(key: 'filter.lenses', type: 'multi', label: 'Lenses available', options: _lenses),
            RegisterFieldSpec(key: 'filter.lighting', type: 'multi', label: 'Lighting gear', options: _lighting),
            RegisterFieldSpec(key: 'filter.photo_extras', type: 'multi', label: 'Photo extras', options: _photoExtras),
            RegisterFieldSpec(key: 'specialties', type: 'multi', label: 'Primary specialties', options: _specialties),
          ],
          pricing: _halfFullPricing,
        ),
    };
  }

  static const _fallbackCities = [
    CatalogOption(id: 'cairo', label: 'Cairo'),
    CatalogOption(id: 'giza', label: 'Giza'),
    CatalogOption(id: 'alexandria', label: 'Alexandria'),
  ];

  static const _fallbackBanks = [
    CatalogOption(id: 'Banque Misr', label: 'Banque Misr'),
    CatalogOption(id: 'National Bank of Egypt', label: 'National Bank of Egypt (NBE)'),
    CatalogOption(id: 'CIB', label: 'Commercial International Bank (CIB)'),
  ];

  static const _fallbackTelecoms = [
    CatalogOption(id: 'vodafone', label: 'Vodafone Cash'),
    CatalogOption(id: 'orange', label: 'Orange Cash'),
    CatalogOption(id: 'etisalat', label: 'e& cash (Etisalat)'),
    CatalogOption(id: 'we', label: 'WE Pay'),
  ];

  static const _fallbackTypes = [
    CatalogOption(id: 'photographer', label: 'Photographer'),
    CatalogOption(id: 'videographer', label: 'Videographer'),
    CatalogOption(id: 'reels', label: 'Mobile Reels Creator'),
    CatalogOption(id: 'studio', label: 'Studio'),
    CatalogOption(id: 'model', label: 'Model'),
    CatalogOption(id: 'ugc', label: 'UGC Creator'),
    CatalogOption(id: 'food_stylist', label: 'Food Stylist'),
  ];

  static const _specialties = [
    CatalogOption(id: 'wedding', label: 'Wedding'),
    CatalogOption(id: 'product', label: 'Product'),
    CatalogOption(id: 'editorial', label: 'Editorial'),
    CatalogOption(id: 'corporate', label: 'Corporate'),
    CatalogOption(id: 'fnb', label: 'Food & drinks'),
    CatalogOption(id: 'events', label: 'Events'),
    CatalogOption(id: 'photosession', label: 'Personal photoshoot'),
  ];

  static const _deliveryFormats = [
    CatalogOption(id: '4K', label: '4K'),
    CatalogOption(id: 'ProRes', label: 'ProRes'),
    CatalogOption(id: 'MP4', label: 'MP4'),
    CatalogOption(id: 'Log', label: 'Log / Rec.709'),
  ];

  static const _cameraTypes = [
    CatalogOption(id: 'full-frame', label: 'Full-frame camera'),
    CatalogOption(id: 'medium-format', label: 'Medium format camera'),
    CatalogOption(id: 'crop-sensor', label: 'Crop sensor camera'),
  ];

  static const _lenses = [
    CatalogOption(id: '35mm', label: '35mm'),
    CatalogOption(id: '50mm', label: '50mm'),
    CatalogOption(id: '85mm', label: '85mm'),
  ];

  static const _lighting = [
    CatalogOption(id: 'strobe', label: 'Strobe / flash'),
    CatalogOption(id: 'continuous', label: 'Continuous LED'),
    CatalogOption(id: 'natural', label: 'Natural light'),
  ];

  static const _photoExtras = [
    CatalogOption(id: 'retouching', label: 'Retouching'),
    CatalogOption(id: 'same-day', label: 'Same-day preview'),
  ];

  static const _videoQuality = [
    CatalogOption(id: '4k', label: '4K'),
    CatalogOption(id: 'hd', label: '1080p'),
    CatalogOption(id: 'vertical', label: 'Vertical / 9:16'),
  ];

  static const _videoGear = [
    CatalogOption(id: 'gimbal', label: 'Gimbal'),
    CatalogOption(id: 'drone', label: 'Drone'),
  ];

  static const _reelCreators = [
    CatalogOption(id: 'iphone', label: 'iPhone creator'),
    CatalogOption(id: 'android', label: 'Android creator'),
  ];

  static const _studioTypes = [
    CatalogOption(id: 'photo-studio', label: 'Photo Studio'),
    CatalogOption(id: 'video-studio', label: 'Video Studio'),
    CatalogOption(id: 'cyclorama', label: 'Cyclorama'),
  ];

  static const _studioAreas = [
    CatalogOption(id: 'cairo', label: 'Cairo'),
    CatalogOption(id: 'new-cairo', label: 'New Cairo'),
    CatalogOption(id: 'zamalek', label: 'Zamalek'),
  ];

  static const _studioFeatures = [
    CatalogOption(id: 'lighting', label: 'Lighting'),
    CatalogOption(id: 'cyclorama', label: 'Cyclorama'),
    CatalogOption(id: 'kitchen', label: 'Kitchen'),
    CatalogOption(id: 'parking', label: 'Parking'),
  ];

  static const _studioSizes = [
    CatalogOption(id: 'under-50', label: 'Under 50'),
    CatalogOption(id: '50-100', label: '50 - 100'),
    CatalogOption(id: '100-200', label: '100 - 200'),
  ];

  static const _modelGenders = [
    CatalogOption(id: 'men', label: 'Men'),
    CatalogOption(id: 'women', label: 'Women'),
    CatalogOption(id: 'kids', label: 'Kids'),
  ];

  static const _modelCategories = [
    CatalogOption(id: 'fashion', label: 'Fashion'),
    CatalogOption(id: 'commercial', label: 'Commercial'),
    CatalogOption(id: 'acting', label: 'Acting'),
  ];

  static const _modelAges = [
    CatalogOption(id: '18-25', label: '18 - 25'),
    CatalogOption(id: '26-35', label: '26 - 35'),
    CatalogOption(id: '36-50', label: '36 - 50'),
  ];

  static const _modelHeights = [
    CatalogOption(id: '160-170', label: '160 - 170'),
    CatalogOption(id: '170-180', label: '170 - 180'),
    CatalogOption(id: '180-190', label: '180 - 190'),
  ];

  static const _modelTops = [
    CatalogOption(id: 's', label: 'S'),
    CatalogOption(id: 'm', label: 'M'),
    CatalogOption(id: 'l', label: 'L'),
  ];

  static const _modelPants = [
    CatalogOption(id: '30', label: '30'),
    CatalogOption(id: '32', label: '32'),
    CatalogOption(id: '34', label: '34'),
  ];

  static const _modelShoes = [
    CatalogOption(id: '40', label: '40'),
    CatalogOption(id: '41', label: '41'),
    CatalogOption(id: '42', label: '42'),
  ];

  static const _ugcNiches = [
    CatalogOption(id: 'beauty', label: 'Beauty'),
    CatalogOption(id: 'fashion', label: 'Fashion'),
    CatalogOption(id: 'food', label: 'Food & Beverages'),
  ];

  static const _ugcCities = [
    CatalogOption(id: 'cairo', label: 'Cairo'),
    CatalogOption(id: 'alexandria', label: 'Alexandria'),
    CatalogOption(id: 'giza', label: 'Giza'),
  ];

  static const _ugcContent = [
    CatalogOption(id: 'reels', label: 'Reels'),
    CatalogOption(id: 'stories', label: 'Stories'),
    CatalogOption(id: 'tiktok', label: 'TikTok'),
  ];

  static const _foodExpertise = [
    CatalogOption(id: 'recipe', label: 'Recipe development'),
    CatalogOption(id: 'styling', label: 'Food styling'),
    CatalogOption(id: 'props', label: 'Props'),
  ];

  static const _foodCuisine = [
    CatalogOption(id: 'egyptian', label: 'Egyptian'),
    CatalogOption(id: 'levantine', label: 'Levantine'),
    CatalogOption(id: 'pastry', label: 'Pastry'),
  ];

  static const _halfFullPricing = [
    RegisterFieldSpec(key: 'half_day_price', type: 'number', label: 'Half-day price (6 hours)', prefix: 'EGP', helper: 'Your rate for a 6-hour booking.'),
    RegisterFieldSpec(key: 'full_day_price', type: 'number', label: 'Full-day price (12 hours)', prefix: 'EGP', helper: 'Your rate for a 12-hour booking.'),
    RegisterFieldSpec(key: 'turnaround_hours', type: 'number', label: 'Post-production turnaround (hours)'),
  ];
}
