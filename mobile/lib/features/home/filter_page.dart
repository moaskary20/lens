import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/shell/app_shell.dart';

class CategoryFilters {
  const CategoryFilters({
    this.gender = 'All',
    this.services = const {},
    this.cities = const {},
    this.age = const RangeValues(9, 50),
    this.height = const RangeValues(160, 200),
    this.tops = const {},
    this.pants = const {},
    this.shoes = const {},
    this.equipment = const {},
    this.contentTypes = const {},
    this.accents = const {},
    this.languages = const {},
    this.ageGroups = const {},
    this.studioSize = const RangeValues(0, 1000),
    this.followers = const RangeValues(0, 1000000),
    this.price = const RangeValues(0, 10000),
    this.minRating,
    this.availability = 'any',
    this.verifiedOnly = false,
    this.vendorSlug,
  });

  final String gender;
  final Set<String> services;
  final Set<String> cities;
  final RangeValues age;
  final RangeValues height;
  final Set<String> tops;
  final Set<String> pants;
  final Set<String> shoes;
  final Set<String> equipment;
  final Set<String> contentTypes;
  final Set<String> accents;
  final Set<String> languages;
  final Set<String> ageGroups;
  final RangeValues studioSize;
  final RangeValues followers;
  final RangeValues price;
  final double? minRating;
  final String availability;
  final bool verifiedOnly;
  final String? vendorSlug;

  static const genders = ['Men', 'Women', 'Kids'];

  static const modelCategories = [
    'Fashion',
    'Commercial',
    'Educational',
    'Lifestyle',
    'Fitness',
    'Promotional',
    'Podcast',
    'Talk',
    'Acting',
    'Corporate',
    'Social Media',
    'Other',
  ];

  static const ageBands = ['9 - 17', '18 - 25', '26 - 35', '36 - 50', '50+'];

  static const heightBands = [
    'Under 160',
    '160 - 170',
    '170 - 180',
    '180 - 190',
    '190 - 200',
    '200+',
  ];

  static const topSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
  static const pantSizes = ['28', '30', '32', '34', '36', '38'];
  static const shoeSizes = [
    '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
    '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32',
    '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45+',
  ];

  static const studioTypes = [
    'All Types',
    'Photo Studio',
    'Video Studio',
    'Podcast Studio',
    'Event Space',
    'Cyclorama',
    'Green Screen',
    'Other',
  ];

  static const studioAreas = [
    'Cairo',
    'New Cairo',
    'Zamalek',
    'Maadi',
    '6th of October',
    'Sheikh Zayed',
    'Nasr City',
    'Heliopolis',
    'Other',
  ];

  static const studioPriceBands = [
    'Under 100',
    '100 - 250',
    '250 - 500',
    '500 - 1,000',
    '1,000 - 2,000',
    '2,000+',
  ];

  static const studioSizeBands = [
    'Under 50',
    '50 - 100',
    '100 - 200',
    '200 - 500',
    '500 - 1,000',
    '1,000+',
  ];

  static const studioEquipment = [
    'All Equipment',
    'Lighting',
    'Green Screen',
    'Cyclorama',
    'Sound Equipment',
    'Podcast Setup',
    'Backdrops',
    'Props',
    'Makeup Room',
    'Changing Room',
    'Parking',
    'Wi-Fi',
    'Air Conditioning',
    'Kitchen',
    'Natural Light',
    'Other',
  ];

  static const studioRatings = ['Any Rating', '4.5+', '4.0+', '3.5+'];

  static const ugcNiches = [
    'All Categories',
    'Beauty',
    'Fashion',
    'Lifestyle',
    'Food & Beverages',
    'Travel',
    'Fitness',
    'Home & Living',
    'Parenting',
    'Gaming',
    'Electronics',
    'Other',
  ];

  static const ugcCities = [
    'All Cities',
    'Cairo',
    'Alexandria',
    'Giza',
    'New Cairo',
    '6th of October',
    'Sharm El Sheikh',
    'Hurghada',
    'Port Said',
    'Suez',
    'Mansoura',
    'Tanta',
    'Assiut',
    'Fayoum',
    'Zagazig',
    'Ismailia',
    'Aswan',
    'Damanhur',
    'Damietta',
    'Minya',
    'Beni Suef',
    'Luxor',
    'Sohag',
    'Qena',
    'Matrouh',
    'Kafr El Sheikh',
    'Arish',
    'Other',
  ];

  static const ugcAccents = [
    'All Accents',
    'Egyptian',
    'Saudi',
    'UAE',
    'Kuwaiti',
    'Shami (Levantine)',
    'Other',
  ];

  static const ugcFollowerBands = [
    'Under 1K',
    '1K - 10K',
    '10K - 50K',
    '50K - 100K',
    '100K - 500K',
    '500K - 1M+',
  ];

  static const ugcPriceBands = [
    'Under 500',
    '500 - 1,000',
    '1,000 - 2,500',
    '2,500 - 5,000',
    '5,000 - 10,000',
    '10,000+',
  ];

  static const ugcContentTypes = [
    'All Types',
    'Video',
    'Unboxing',
    'Product Demo',
    'Testimonial',
    'Tutorial',
    'Lifestyle',
    'Trend / Challenge',
    'UGC Ads',
    'Voiceover',
    'Other',
  ];

  static const ugcGenders = ['All', 'Men', 'Women', 'Non-binary'];
  static const ugcAges = ['All Ages', '18 - 24', '25 - 34', '35 - 44', '45+'];
  static const ugcLanguages = ['All Languages', 'Arabic', 'English', 'Bilingual', 'Other'];

  static const foodExpertise = [
    'All Expertise',
    'Recipe Development',
    'Food Styling',
    'Food Photography',
    'Food Videography',
    'Props Styling',
  ];

  static const foodCuisines = [
    'All Cuisines',
    'Egyptian',
    'Italian',
    'Asian',
    'Middle Eastern',
    'Healthy',
    'Desserts & Bakery',
    'Beverages',
    'Home Cooking',
    'Fine Dining',
    'Other',
  ];

  static const foodCities = [
    'All Cities',
    'Cairo',
    'Alexandria',
    'Giza',
    'Sharm El Sheikh',
    'Hurghada',
    'Mansoura',
    'Tanta',
    'Ismailia',
    'Suez',
    'Luxor',
    'Aswan',
    'Port Said',
    'Damietta',
    'Zagazig',
    'Minya',
    'Sohag',
    'Qena',
    'Beni Suef',
    'Other',
  ];

  static const foodContentTypes = [
    'All Types',
    'Photo',
    'Video',
    'Recipe Video',
    'Tutorial',
    'Product Shoot',
    'Lifestyle',
    'Social Media Content',
  ];

  static const serviceOptions = [
    'F&B',
    'Events',
    'Weddings',
    'Portrait',
    'Product',
    'Fashion',
    'Lifestyle',
    'Corporate',
    'Real Estate',
    'E-commerce',
    'Motors',
    'Jewelry',
    'Furniture',
  ];

  static const cityOptions = [
    'Cairo',
    'Alexandria',
    'Giza',
    'Shubra El Kheima',
    'Port Said',
    'Suez',
    'Mansoura',
    'El Mahalla El Kubra',
    'Tanta',
    'Assiut',
    'Fayoum',
    'Zagazig',
    'Ismailia',
    'Aswan',
    'Damanhur',
    'Damietta',
    'Minya',
    'Beni Suef',
    'Luxor',
    'Sohag',
    'Qena',
    'Hurghada',
    'Arish',
    'Kafr El Sheikh',
    'Matrouh',
    '6th of October',
    '10th of Ramadan',
  ];

  static const CategoryFilters modelPreview = CategoryFilters(
    gender: 'Men',
    services: {'Fashion'},
    cities: {'Cairo'},
    age: RangeValues(9, 50),
    height: RangeValues(160, 200),
    tops: {'M'},
    pants: {'32'},
    shoes: {'41'},
    price: RangeValues(500, 10000),
  );

  static const CategoryFilters studioPreview = CategoryFilters(
    services: {'All Types'},
    cities: {'Cairo'},
    equipment: {'All Equipment'},
    studioSize: RangeValues(50, 500),
    price: RangeValues(100, 2000),
  );

  static const CategoryFilters ugcPreview = CategoryFilters(
    gender: 'All',
    services: {'All Categories'},
    cities: {'All Cities'},
    accents: {'All Accents'},
    contentTypes: {'All Types'},
    ageGroups: {'All Ages'},
    languages: {'All Languages'},
    followers: RangeValues(1000, 1000000),
    price: RangeValues(500, 10000),
  );

  static const CategoryFilters foodPreview = CategoryFilters(
    services: {'All Expertise'},
    accents: {'All Cuisines'},
    cities: {'All Cities'},
    contentTypes: {'All Types'},
    price: RangeValues(500, 10000),
  );

  CategoryFilters copyWith({
    String? gender,
    Set<String>? services,
    Set<String>? cities,
    RangeValues? age,
    RangeValues? height,
    Set<String>? tops,
    Set<String>? pants,
    Set<String>? shoes,
    Set<String>? equipment,
    Set<String>? contentTypes,
    Set<String>? accents,
    Set<String>? languages,
    Set<String>? ageGroups,
    RangeValues? studioSize,
    RangeValues? followers,
    RangeValues? price,
    double? minRating,
    bool clearRating = false,
    String? availability,
    bool? verifiedOnly,
    String? vendorSlug,
  }) {
    return CategoryFilters(
      gender: gender ?? this.gender,
      services: services ?? this.services,
      cities: cities ?? this.cities,
      age: age ?? this.age,
      height: height ?? this.height,
      tops: tops ?? this.tops,
      pants: pants ?? this.pants,
      shoes: shoes ?? this.shoes,
      equipment: equipment ?? this.equipment,
      contentTypes: contentTypes ?? this.contentTypes,
      accents: accents ?? this.accents,
      languages: languages ?? this.languages,
      ageGroups: ageGroups ?? this.ageGroups,
      studioSize: studioSize ?? this.studioSize,
      followers: followers ?? this.followers,
      price: price ?? this.price,
      minRating: clearRating ? null : (minRating ?? this.minRating),
      availability: availability ?? this.availability,
      verifiedOnly: verifiedOnly ?? this.verifiedOnly,
      vendorSlug: vendorSlug ?? this.vendorSlug,
    );
  }

  bool get isEmpty => isClearedFor(vendorSlug);

  bool isClearedFor(String? slug) {
    final type = (slug ?? vendorSlug ?? '').toLowerCase();
    final genderOk = switch (type) {
      'model' || 'ugc' => gender == 'All',
      _ => true,
    };
    return services.isEmpty &&
        cities.isEmpty &&
        tops.isEmpty &&
        pants.isEmpty &&
        shoes.isEmpty &&
        equipment.isEmpty &&
        contentTypes.isEmpty &&
        accents.isEmpty &&
        languages.isEmpty &&
        ageGroups.isEmpty &&
        genderOk &&
        isDefaultPrice(type) &&
        isDefaultStudioSize(type) &&
        isDefaultFollowers(type) &&
        minRating == null &&
        availability == 'any' &&
        !verifiedOnly;
  }

  bool isDefaultPrice(String? slug) {
    final type = (slug ?? vendorSlug ?? '').toLowerCase();
    if (type == 'studio') {
      return price.start <= 0 && price.end >= 2000;
    }
    if (type == 'model' || type == 'ugc' || type == 'food_stylist') {
      return price.start <= 500 && price.end >= 10000;
    }
    return price.start <= 0 && price.end >= 10000;
  }

  bool isDefaultStudioSize(String? slug) {
    final type = (slug ?? vendorSlug ?? '').toLowerCase();
    if (type != 'studio') {
      return true;
    }
    return studioSize.start <= 0 && studioSize.end >= 1000;
  }

  bool isDefaultFollowers(String? slug) {
    final type = (slug ?? vendorSlug ?? '').toLowerCase();
    if (type != 'ugc') {
      return true;
    }
    return followers.start <= 0 && followers.end >= 1000000;
  }
}

class FilterPage extends StatefulWidget {
  const FilterPage({
    super.key,
    required this.title,
    this.vendorTypeSlug,
    this.initial = const CategoryFilters(),
    this.catalog = const [],
    this.types = const [],
  });

  final String title;
  final String? vendorTypeSlug;
  final CategoryFilters initial;
  final List<FilterGroupItem> catalog;
  final List<VendorTypeItem> types;

  @override
  State<FilterPage> createState() => _FilterPageState();
}

class _FilterPageState extends State<FilterPage> {
  late CategoryFilters _filters;
  late String? _slug;
  late final TextEditingController _cityQuery;
  final _open = <String>{
    'category',
    'service',
    'type',
    'age',
    'height',
    'size',
    'location',
    'price',
    'studioSize',
    'features',
    'rating',
    'availability',
    'verified',
    'niche',
    'accent',
    'followers',
    'content',
    'gender',
    'ugcAge',
    'language',
  };

  String get _activeSlug => (_slug ?? widget.vendorTypeSlug ?? '').toLowerCase();

  bool get _hasTypePicker => widget.types.length > 1;

  bool get _isModel => _activeSlug == 'model' || (!_hasTypePicker && widget.title.toLowerCase().contains('model'));

  bool get _isStudio => _activeSlug == 'studio' || (!_hasTypePicker && widget.title.toLowerCase().contains('studio'));

  bool get _isUgc => _activeSlug == 'ugc' || (!_hasTypePicker && widget.title.toLowerCase().contains('ugc'));

  bool get _isFood =>
      _activeSlug == 'food_stylist' || (!_hasTypePicker && widget.title.toLowerCase().contains('food'));

  @override
  void initState() {
    super.initState();
    _slug = widget.initial.vendorSlug ?? widget.vendorTypeSlug;
    _filters = widget.initial.isEmpty ? _clearedForType : widget.initial;
    _filters = _normalizeRanges(_filters);
    _cityQuery = TextEditingController();
  }

  CategoryFilters _normalizeRanges(CategoryFilters filters) {
    if (_isStudio) {
      return filters.copyWith(
        price: _clampRange(filters.price, 0, 2000),
        studioSize: _clampRange(filters.studioSize, 0, 1000),
      );
    }
    if (_isModel) {
      final kids = filters.gender == 'Kids';
      return filters.copyWith(
        price: _clampRange(filters.price, 500, 10000),
        age: _clampRange(filters.age, 0, kids ? 17 : 60),
        height: _clampRange(filters.height, kids ? 80 : 140, 210),
      );
    }
    if (_isUgc) {
      return filters.copyWith(
        price: _clampRange(filters.price, 0, 10000),
        followers: _clampRange(filters.followers, 0, 1000000),
      );
    }
    if (_isFood) {
      return filters.copyWith(price: _clampRange(filters.price, 0, 10000));
    }
    return filters.copyWith(price: _clampRange(filters.price, 0, 10000));
  }

  /// Neutral defaults for the active vendor type (no chips selected).
  CategoryFilters get _clearedForType {
    if (_isModel) {
      return const CategoryFilters(
        gender: 'All',
        age: RangeValues(9, 50),
        height: RangeValues(160, 200),
        price: RangeValues(500, 10000),
        vendorSlug: 'model',
      );
    }
    if (_isStudio) {
      return const CategoryFilters(
        studioSize: RangeValues(0, 1000),
        price: RangeValues(0, 2000),
        vendorSlug: 'studio',
      );
    }
    if (_isUgc) {
      return const CategoryFilters(
        gender: 'All',
        followers: RangeValues(0, 1000000),
        price: RangeValues(0, 10000),
        vendorSlug: 'ugc',
      );
    }
    if (_isFood) {
      return const CategoryFilters(
        price: RangeValues(0, 10000),
        vendorSlug: 'food_stylist',
      );
    }
    return CategoryFilters(vendorSlug: _activeSlug);
  }

  CategoryFilters get _previewForType => _clearedForType;

  @override
  void dispose() {
    _cityQuery.dispose();
    super.dispose();
  }

  List<String> _labels(String slug, List<String> fallback) {
    for (final group in widget.catalog) {
      if (group.slug == slug && group.options.isNotEmpty) {
        return group.options.map((option) => option.label).toList();
      }
    }
    return fallback;
  }

  void _toggle(String value, {required Set<String> current, required void Function(Set<String>) apply, bool single = false}) {
    final next = {...current};
    if (single) {
      next
        ..clear()
        ..add(value);
    } else if (next.contains(value)) {
      next.remove(value);
    } else {
      next.add(value);
    }
    setState(() => apply(next));
  }

  String get _currentTitle {
    VendorTypeItem? type;
    for (final item in widget.types) {
      if (item.slug.toLowerCase() == _activeSlug) {
        type = item;
        break;
      }
    }
    final name = type?.label ?? widget.title;
    if (_isModel && _filters.gender != 'All') {
      return 'Filter $name (${_filters.gender})';
    }
    return 'Filter $name';
  }

  int get _activeCount {
    var count = 0;
    if (_filters.services.isNotEmpty) count += 1;
    if (_filters.cities.isNotEmpty) count += 1;
    if (_filters.tops.isNotEmpty || _filters.pants.isNotEmpty || _filters.shoes.isNotEmpty) count += 1;
    if (_filters.equipment.isNotEmpty) count += 1;
    if (_filters.contentTypes.isNotEmpty) count += 1;
    if (_filters.accents.isNotEmpty) count += 1;
    if (_filters.languages.isNotEmpty) count += 1;
    if (_filters.ageGroups.isNotEmpty) count += 1;
    if (_filters.minRating != null) count += 1;
    if (_filters.availability != 'any') count += 1;
    if (_filters.verifiedOnly) count += 1;
    return count;
  }

  @override
  Widget build(BuildContext context) {
    final title = _currentTitle;

    return Scaffold(
      backgroundColor: const Color(0xFF09090B),
      bottomNavigationBar: AppShell.navBar(context, index: AppShell.searchIndex),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Color(0xFF1A0E0A), Color(0xFF09090B), Color(0xFF09090B)],
            stops: [0, 0.22, 1],
          ),
        ),
        child: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 4),
              child: Row(
                children: [
                  _roundIconButton(
                    icon: Icons.close_rounded,
                    onTap: () => Navigator.of(context).pop(),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          _activeCount == 0 ? 'Refine results' : '$_activeCount filters selected',
                          style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                  TextButton(
                    key: const Key('filter-reset-all'),
                    onPressed: _resetAll,
                    child: const Text('Reset All', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                cacheExtent: 2000,
                padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
                children: [
                  if (_hasTypePicker) ...[
                    _typePicker(),
                    const SizedBox(height: 14),
                  ],
                  if (_isFood) ..._foodSections() else if (_isUgc) ..._ugcSections() else if (_isStudio) ...[
                    _section(
                      id: 'type',
                      icon: Icons.view_in_ar_outlined,
                      title: 'Studio Type',
                      child: _chips(
                        _labels('studio_type', CategoryFilters.studioTypes),
                        _filters.services,
                        (value) => _toggleAll(
                          value,
                          allLabel: 'All Types',
                          current: _filters.services,
                          apply: (next) => _filters = _filters.copyWith(services: next),
                        ),
                      ),
                    ),
                  ] else if (_isModel) ...[
                    _genderTabs(),
                    const SizedBox(height: 12),
                    _section(
                      id: 'category',
                      icon: Icons.person_outline,
                      title: 'Category',
                      child: _chips(
                        _labels('model_category', CategoryFilters.modelCategories),
                        _filters.services,
                        (value) => _toggle(value, current: _filters.services, apply: (next) => _filters = _filters.copyWith(services: next)),
                      ),
                    ),
                    _section(
                      id: 'age',
                      icon: Icons.calendar_today_outlined,
                      title: 'Age Range',
                      trailing: '${_filters.age.start.round()} - ${_filters.age.end.round()} years',
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: 9,
                            max: _filters.gender == 'Kids' ? 17 : 60,
                            values: _filters.age,
                            unitSuffix: ' yrs',
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(age: value)),
                          ),
                          _chips(
                            _labels(
                              'model_age',
                              _filters.gender == 'Kids'
                                  ? const ['9 - 17']
                                  : CategoryFilters.ageBands,
                            ),
                            _selectedAgeBands,
                            _selectAgeBand,
                          ),
                        ],
                      ),
                    ),
                    _section(
                      id: 'height',
                      icon: Icons.straighten_rounded,
                      title: 'Height (cm)',
                      trailing: '${_filters.height.start.round()} - ${_filters.height.end.round()} cm',
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: _filters.gender == 'Kids' ? 80 : 140,
                            max: 210,
                            values: _filters.height,
                            unitSuffix: ' cm',
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(height: value)),
                          ),
                          _chips(
                            _labels('model_height', CategoryFilters.heightBands),
                            _selectedHeightBands,
                            _selectHeightBand,
                          ),
                        ],
                      ),
                    ),
                    _section(
                      id: 'size',
                      icon: Icons.checkroom_outlined,
                      title: 'Size (Clothes & Shoes)',
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _sizeRow(
                            icon: Icons.checkroom_outlined,
                            label: 'T-Shirt / Tops',
                            options: _labels('model_tops', CategoryFilters.topSizes),
                            selected: _filters.tops,
                            onTap: (value) => _toggle(value, current: _filters.tops, apply: (next) => _filters = _filters.copyWith(tops: next)),
                          ),
                          const SizedBox(height: 14),
                          _sizeRow(
                            icon: Icons.accessibility_new_rounded,
                            label: 'Pants / Bottoms (Waist)',
                            options: _labels('model_pants', CategoryFilters.pantSizes),
                            selected: _filters.pants,
                            onTap: (value) => _toggle(value, current: _filters.pants, apply: (next) => _filters = _filters.copyWith(pants: next)),
                          ),
                          const SizedBox(height: 14),
                          _sizeRow(
                            icon: Icons.ice_skating_outlined,
                            label: 'Shoes (EU)',
                            options: _labels('model_shoes', CategoryFilters.shoeSizes),
                            selected: _filters.shoes,
                            onTap: (value) => _toggle(value, current: _filters.shoes, apply: (next) => _filters = _filters.copyWith(shoes: next)),
                          ),
                        ],
                      ),
                    ),
                  ] else
                    _section(
                      id: 'service',
                      icon: Icons.person_outline,
                      title: 'Service Type',
                      child: _chips(
                        CategoryFilters.serviceOptions,
                        _filters.services,
                        (value) => _toggle(value, current: _filters.services, apply: (next) => _filters = _filters.copyWith(services: next)),
                        showChevron: true,
                      ),
                    ),
                  if (!_isUgc && !_isFood) ...[
                  _section(
                    id: 'location',
                    icon: Icons.location_on_outlined,
                    title: 'Location',
                    child: Column(
                      children: [
                        if (_isModel || _isStudio) ...[
                          _citySearch(),
                          const SizedBox(height: 12),
                        ],
                        _chips(
                          _visibleCities,
                          _filters.cities,
                          (value) => _toggle(value, current: _filters.cities, apply: (next) => _filters = _filters.copyWith(cities: next)),
                        ),
                      ],
                    ),
                  ),
                  if (_isStudio) ...[
                    _section(
                      id: 'price',
                      icon: Icons.payments_outlined,
                      title: 'Price Range (EGP/hour)',
                      trailing: _priceTrailing(_filters.price, min: 0, max: 2000),
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: 0,
                            max: 2000,
                            values: _filters.price,
                            unitPrefix: 'EGP ',
                            openEndedMax: true,
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(price: value)),
                          ),
                          _chips(
                            _labels('studio_hourly', CategoryFilters.studioPriceBands),
                            _selectedStudioPriceBands,
                            _selectStudioPriceBand,
                          ),
                        ],
                      ),
                    ),
                    _section(
                      id: 'studioSize',
                      icon: Icons.open_with_rounded,
                      title: 'Studio Size (m²)',
                      trailing: '${_filters.studioSize.start.round()} - ${_filters.studioSize.end.round()}+ m²',
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: 0,
                            max: 1000,
                            values: _filters.studioSize,
                            unitSuffix: ' m²',
                            openEndedMax: true,
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(studioSize: value)),
                          ),
                          _chips(
                            _labels('studio_size', CategoryFilters.studioSizeBands),
                            _selectedStudioSizeBands,
                            _selectStudioSizeBand,
                          ),
                        ],
                      ),
                    ),
                    _section(
                      id: 'features',
                      icon: Icons.tune_rounded,
                      title: 'Features & Equipment',
                      child: _chips(
                        _labels('studio_features', CategoryFilters.studioEquipment),
                        _filters.equipment,
                        (value) => _toggleAll(
                          value,
                          allLabel: 'All Equipment',
                          current: _filters.equipment,
                          apply: (next) => _filters = _filters.copyWith(equipment: next),
                        ),
                      ),
                    ),
                  ] else
                    _section(
                      id: 'price',
                      icon: Icons.calendar_view_day_outlined,
                      title: 'Price Range (EGP)',
                      trailing: _priceTrailing(
                        _filters.price,
                        min: _isModel ? 500 : 0,
                        max: 10000,
                      ),
                      child: _rangeSlider(
                        min: _isModel ? 500 : 0,
                        max: 10000,
                        values: _filters.price,
                        unitPrefix: 'EGP ',
                        openEndedMax: true,
                        onChanged: (value) => setState(() => _filters = _filters.copyWith(price: value)),
                      ),
                    ),
                  if (_isStudio)
                    _section(
                      id: 'availability',
                      icon: Icons.calendar_today_outlined,
                      title: 'Availability',
                      child: _chips(
                        const ['Any Date', 'This Week', 'This Month', 'Custom Date'],
                        {_availabilityLabel},
                        _selectAvailability,
                        single: true,
                      ),
                    ),
                  _section(
                    id: 'rating',
                    icon: Icons.star_border_rounded,
                    title: _isStudio ? 'Rating' : 'Minimum Rating',
                    child: _chips(
                      _isStudio
                          ? CategoryFilters.studioRatings
                          : _labels('rating', const ['Any', '4.0+', '4.5+', '4.8+']).where(_isRatingChip).toList(),
                      _isStudio
                          ? {
                              if (_filters.minRating == null) 'Any Rating',
                              if (_filters.minRating == 4.5) '4.5+',
                              if (_filters.minRating == 4.0) '4.0+',
                              if (_filters.minRating == 3.5) '3.5+',
                            }
                          : {
                        if (_filters.minRating == null) 'Any',
                        if (_filters.minRating == 4.0) '4.0+',
                        if (_filters.minRating == 4.5) '4.5+',
                        if (_filters.minRating == 4.8) '4.8+',
                      },
                      (value) {
                        setState(() {
                          _filters = switch (value) {
                            '3.5+' => _filters.copyWith(minRating: 3.5),
                            '4.0+' => _filters.copyWith(minRating: 4.0),
                            '4.5+' => _filters.copyWith(minRating: 4.5),
                            '4.8+' => _filters.copyWith(minRating: 4.8),
                            _ => _filters.copyWith(clearRating: true),
                          };
                        });
                      },
                      single: true,
                    ),
                  ),
                  if (!_isStudio)
                    _section(
                      id: 'availability',
                      icon: Icons.calendar_today_outlined,
                      title: 'Availability',
                      child: _chips(
                        _labels('availability', const ['Any Date', 'This Week', 'This Month', 'Custom Date']).where(_isAvailabilityChip).toList(),
                        {_availabilityLabel},
                        _selectAvailability,
                        single: true,
                      ),
                    ),
                  if (_isStudio) _verifiedToggle(),
                  ],
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 16),
              decoration: const BoxDecoration(
                color: Color(0xF00B0B0D),
                border: Border(top: BorderSide(color: Color(0xFF22242A))),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: _bottomButton(
                      label: 'Cancel',
                      background: const Color(0xFF17181C),
                      foreground: Colors.white,
                      onTap: () => Navigator.of(context).pop(),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: _bottomButton(
                      label: 'Apply Filters',
                      background: LensColors.primary,
                      foreground: Colors.white,
                      onTap: () => Navigator.of(context).pop(_filters.copyWith(vendorSlug: _activeSlug.isEmpty ? null : _activeSlug)),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      ),
    );
  }

  Widget _roundIconButton({required IconData icon, required VoidCallback onTap}) {
    return Material(
      color: const Color(0xFF17181C),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 42,
          height: 42,
          child: Icon(icon, color: Colors.white, size: 22),
        ),
      ),
    );
  }

  Widget _typePicker() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Creator type', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
        const SizedBox(height: 4),
        const Text('Choose who you want to book, then refine below.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: widget.types.map((type) {
            final selected = type.slug.toLowerCase() == _activeSlug;
            return GestureDetector(
              onTap: () => setState(() {
                _slug = type.slug;
                _filters = _previewForType;
                _cityQuery.clear();
              }),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 160),
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: selected ? LensColors.primary : const Color(0xFF141518),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2D34)),
                ),
                child: Text(
                  type.label,
                  style: TextStyle(
                    color: selected ? Colors.white : const Color(0xFFD0CBC3),
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  List<String> get _visibleCities {
    final cities = _isStudio
        ? _labels('studio_areas', CategoryFilters.studioAreas)
        : _labels('search_cities', CategoryFilters.cityOptions);
    final query = _cityQuery.text.trim().toLowerCase();
    if (query.isEmpty) {
      return cities;
    }
    return cities.where((city) => city.toLowerCase().contains(query)).toList();
  }

  void _toggleAll(
    String value, {
    required String allLabel,
    required Set<String> current,
    required void Function(Set<String>) apply,
  }) {
    final next = {...current};
    if (value == allLabel) {
      next
        ..clear()
        ..add(allLabel);
    } else {
      next.remove(allLabel);
      if (next.contains(value)) {
        next.remove(value);
      } else {
        next.add(value);
      }
      if (next.isEmpty) {
        next.add(allLabel);
      }
    }
    setState(() => apply(next));
  }

  Future<void> _selectAvailability(String value) async {
    if (value == 'Custom Date') {
      await showDatePicker(
        context: context,
        initialDate: DateTime.now(),
        firstDate: DateTime.now(),
        lastDate: DateTime.now().add(const Duration(days: 365)),
      );
    }
    setState(() {
      _filters = _filters.copyWith(
        availability: switch (value) {
          'This Week' => 'week',
          'This Month' => 'month',
          'Custom Date' => 'custom',
          _ => 'any',
        },
      );
    });
  }

  Set<String> get _selectedAgeBands => switch ((_filters.age.start.round(), _filters.age.end.round())) {
        (9, 17) => {'9 - 17'},
        (18, 25) => {'18 - 25'},
        (26, 35) => {'26 - 35'},
        (36, 50) => {'36 - 50'},
        (50, 60) => {'50+'},
        _ => {},
      };

  Set<String> get _selectedHeightBands => switch ((_filters.height.start.round(), _filters.height.end.round())) {
        (140, 159) => {'Under 160'},
        (160, 170) => {'160 - 170'},
        (170, 180) => {'170 - 180'},
        (180, 190) => {'180 - 190'},
        (190, 200) => {'190 - 200'},
        (200, 210) => {'200+'},
        _ => {},
      };

  void _selectAgeBand(String value) {
    final range = switch (value) {
      '9 - 17' || 'Under 18' => const RangeValues(9, 17),
      '18 - 25' => const RangeValues(18, 25),
      '26 - 35' => const RangeValues(26, 35),
      '36 - 50' => const RangeValues(36, 50),
      '50+' => const RangeValues(50, 60),
      _ => _filters.age,
    };
    setState(() => _filters = _filters.copyWith(age: range));
  }

  Set<String> get _selectedStudioPriceBands => switch ((_filters.price.start.round(), _filters.price.end.round())) {
        (0, 99) => {'Under 100'},
        (100, 250) => {'100 - 250'},
        (250, 500) => {'250 - 500'},
        (500, 1000) => {'500 - 1,000'},
        (1000, 2000) => {'1,000 - 2,000'},
        (2000, 2000) => {'2,000+'},
        _ => {},
      };

  Set<String> get _selectedStudioSizeBands => switch ((_filters.studioSize.start.round(), _filters.studioSize.end.round())) {
        (0, 49) => {'Under 50'},
        (50, 100) => {'50 - 100'},
        (100, 200) => {'100 - 200'},
        (200, 500) => {'200 - 500'},
        (500, 1000) => {'500 - 1,000'},
        (1000, 1000) => {'1,000+'},
        _ => {},
      };

  void _selectStudioPriceBand(String value) {
    final range = switch (value) {
      'Under 100' => const RangeValues(0, 99),
      '100 - 250' => const RangeValues(100, 250),
      '250 - 500' => const RangeValues(250, 500),
      '500 - 1,000' => const RangeValues(500, 1000),
      '1,000 - 2,000' => const RangeValues(1000, 2000),
      '2,000+' => const RangeValues(2000, 2000),
      _ => _filters.price,
    };
    setState(() => _filters = _filters.copyWith(price: range));
  }

  void _selectStudioSizeBand(String value) {
    final range = switch (value) {
      'Under 50' => const RangeValues(0, 49),
      '50 - 100' => const RangeValues(50, 100),
      '100 - 200' => const RangeValues(100, 200),
      '200 - 500' => const RangeValues(200, 500),
      '500 - 1,000' => const RangeValues(500, 1000),
      '1,000+' => const RangeValues(1000, 1000),
      _ => _filters.studioSize,
    };
    setState(() => _filters = _filters.copyWith(studioSize: range));
  }

  Widget _verifiedToggle() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 14, 12, 14),
        decoration: BoxDecoration(
          color: const Color(0xFF121316),
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: _filters.verifiedOnly ? const Color(0x66FF5A1F) : const Color(0xFF22242A)),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: const Color(0xFF2A140E),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.verified_outlined, color: LensColors.primary, size: 20),
            ),
            const SizedBox(width: 12),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Verified Only', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
                  SizedBox(height: 2),
                  Text('Show only verified studios', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                ],
              ),
            ),
            Switch(
              value: _filters.verifiedOnly,
              activeThumbColor: Colors.white,
              activeTrackColor: LensColors.primary,
              onChanged: (value) => setState(() => _filters = _filters.copyWith(verifiedOnly: value)),
            ),
          ],
        ),
      ),
    );
  }

  void _selectHeightBand(String value) {
    final range = switch (value) {
      'Under 160' => const RangeValues(140, 159),
      '160 - 170' => const RangeValues(160, 170),
      '170 - 180' => const RangeValues(170, 180),
      '180 - 190' => const RangeValues(180, 190),
      '190 - 200' => const RangeValues(190, 200),
      '200+' => const RangeValues(200, 210),
      _ => _filters.height,
    };
    setState(() => _filters = _filters.copyWith(height: range));
  }

  bool _isRatingChip(String label) => const {'Any', '4.0+', '4.5+', '4.8+'}.contains(label);

  bool _isAvailabilityChip(String label) => const {'Any Date', 'This Week', 'This Month', 'Custom Date'}.contains(label);

  String get _availabilityLabel => switch (_filters.availability) {
        'week' => 'This Week',
        'month' => 'This Month',
        'custom' => 'Custom Date',
        _ => 'Any Date',
      };

  List<Widget> _foodSections() {
    return [
      _section(
        id: 'niche',
        icon: Icons.grid_view_rounded,
        title: 'Category / Expertise',
        child: _chips(
          _labels('food_expertise', CategoryFilters.foodExpertise),
          _filters.services,
          (value) => _toggleAll(value, allLabel: 'All Expertise', current: _filters.services, apply: (next) => _filters = _filters.copyWith(services: next)),
        ),
      ),
      _section(
        id: 'accent',
        icon: Icons.restaurant_outlined,
        title: 'Cuisine / Food Type',
        child: _chips(
          _labels('food_cuisine', CategoryFilters.foodCuisines),
          _filters.accents,
          (value) => _toggleAll(value, allLabel: 'All Cuisines', current: _filters.accents, apply: (next) => _filters = _filters.copyWith(accents: next)),
        ),
      ),
      _section(
        id: 'location',
        icon: Icons.location_on_outlined,
        title: 'Egyptian Cities',
        child: _chips(
          _labels('food_cities', CategoryFilters.foodCities),
          _filters.cities,
          (value) => _toggleAll(value, allLabel: 'All Cities', current: _filters.cities, apply: (next) => _filters = _filters.copyWith(cities: next)),
        ),
      ),
      _section(
        id: 'price',
        icon: Icons.payments_outlined,
        title: 'Price Range (EGP / video)',
        trailing: _priceTrailing(_filters.price, min: 0, max: 10000),
        child: Column(
          children: [
            _rangeSlider(
              min: 0,
              max: 10000,
              values: _filters.price,
              unitPrefix: 'EGP ',
              openEndedMax: true,
              onChanged: (value) => setState(() => _filters = _filters.copyWith(price: value)),
            ),
            _chips(_labels('food_price', CategoryFilters.ugcPriceBands), _selectedUgcPriceBands, _selectUgcPriceBand),
          ],
        ),
      ),
      _section(
        id: 'content',
        icon: Icons.smart_display_outlined,
        title: 'Content Type',
        child: _chips(
          _labels('food_content', CategoryFilters.foodContentTypes),
          _filters.contentTypes,
          (value) => _toggleAll(value, allLabel: 'All Types', current: _filters.contentTypes, apply: (next) => _filters = _filters.copyWith(contentTypes: next)),
        ),
      ),
    ];
  }

  List<Widget> _ugcSections() {
    return [
      _section(
        id: 'niche',
        icon: Icons.grid_view_rounded,
        title: 'Category / Niche',
        child: _chips(
          _labels('ugc_niche', CategoryFilters.ugcNiches),
          _filters.services,
          (value) => _toggleAll(value, allLabel: 'All Categories', current: _filters.services, apply: (next) => _filters = _filters.copyWith(services: next)),
        ),
      ),
      _section(
        id: 'location',
        icon: Icons.location_city_outlined,
        title: 'City (Egypt)',
        child: _chips(
          _labels('ugc_cities', CategoryFilters.ugcCities),
          _filters.cities,
          (value) => _toggleAll(value, allLabel: 'All Cities', current: _filters.cities, apply: (next) => _filters = _filters.copyWith(cities: next)),
        ),
      ),
      _section(
        id: 'accent',
        icon: Icons.record_voice_over_outlined,
        title: 'Accent',
        child: _chips(
          _labels('ugc_accent', CategoryFilters.ugcAccents),
          _filters.accents,
          (value) => _toggleAll(value, allLabel: 'All Accents', current: _filters.accents, apply: (next) => _filters = _filters.copyWith(accents: next)),
        ),
      ),
      _section(
        id: 'followers',
        icon: Icons.groups_outlined,
        title: 'Followers Count',
        trailing: _followersLabel,
        child: Column(
          children: [
            _rangeSlider(
              min: 0,
              max: 1000000,
              values: _filters.followers,
              compact: true,
              openEndedMax: true,
              onChanged: (value) => setState(() => _filters = _filters.copyWith(followers: value)),
            ),
            _chips(_labels('ugc_followers', CategoryFilters.ugcFollowerBands), _selectedFollowerBands, _selectFollowerBand),
          ],
        ),
      ),
      _section(
        id: 'price',
        icon: Icons.payments_outlined,
        title: 'Price Range (EGP / video)',
        trailing: _priceTrailing(_filters.price, min: 0, max: 10000),
        child: Column(
          children: [
            _rangeSlider(
              min: 0,
              max: 10000,
              values: _filters.price,
              unitPrefix: 'EGP ',
              openEndedMax: true,
              onChanged: (value) => setState(() => _filters = _filters.copyWith(price: value)),
            ),
            _chips(_labels('ugc_price', CategoryFilters.ugcPriceBands), _selectedUgcPriceBands, _selectUgcPriceBand),
          ],
        ),
      ),
      _section(
        id: 'content',
        icon: Icons.smart_display_outlined,
        title: 'Content Type',
        child: _chips(
          _labels('ugc_content', CategoryFilters.ugcContentTypes),
          _filters.contentTypes,
          (value) => _toggleAll(value, allLabel: 'All Types', current: _filters.contentTypes, apply: (next) => _filters = _filters.copyWith(contentTypes: next)),
        ),
      ),
      _section(
        id: 'gender',
        icon: Icons.person_outline,
        title: 'Gender',
        child: _chips(
          _labels('ugc_gender', CategoryFilters.ugcGenders),
          {_filters.gender},
          (value) => setState(() => _filters = _filters.copyWith(gender: value)),
          single: true,
        ),
      ),
      _section(
        id: 'ugcAge',
        icon: Icons.calendar_today_outlined,
        title: 'Age range',
        child: _chips(
          _labels('ugc_age', CategoryFilters.ugcAges),
          _filters.ageGroups,
          (value) => _toggleAll(value, allLabel: 'All Ages', current: _filters.ageGroups, apply: (next) => _filters = _filters.copyWith(ageGroups: next)),
        ),
      ),
      _section(
        id: 'language',
        icon: Icons.language_rounded,
        title: 'Language',
        child: _chips(
          _labels('ugc_language', CategoryFilters.ugcLanguages),
          _filters.languages,
          (value) => _toggleAll(value, allLabel: 'All Languages', current: _filters.languages, apply: (next) => _filters = _filters.copyWith(languages: next)),
        ),
      ),
      _section(
        id: 'availability',
        icon: Icons.schedule_outlined,
        title: 'Availability',
        child: _chips(
          const ['Any Time', 'This Week', 'This Month', 'Custom Date'],
          {_ugcAvailabilityLabel},
          _selectAvailability,
          single: true,
        ),
      ),
    ];
  }

  String get _followersLabel {
    String fmt(double value) {
      if (value >= 1000000) {
        return '1M+';
      }
      if (value >= 1000) {
        return '${(value / 1000).round()}K';
      }
      return value.round().toString();
    }

    return '${fmt(_filters.followers.start)} - ${fmt(_filters.followers.end)}';
  }

  Set<String> get _selectedFollowerBands => switch ((_filters.followers.start.round(), _filters.followers.end.round())) {
        (0, 999) => {'Under 1K'},
        (1000, 10000) => {'1K - 10K'},
        (10000, 50000) => {'10K - 50K'},
        (50000, 100000) => {'50K - 100K'},
        (100000, 500000) => {'100K - 500K'},
        (500000, 1000000) => {'500K - 1M+'},
        _ => {},
      };

  Set<String> get _selectedUgcPriceBands => switch ((_filters.price.start.round(), _filters.price.end.round())) {
        (0, 499) => {'Under 500'},
        (500, 1000) => {'500 - 1,000'},
        (1000, 2500) => {'1,000 - 2,500'},
        (2500, 5000) => {'2,500 - 5,000'},
        (5000, 10000) => {'5,000 - 10,000'},
        (10000, 10000) => {'10,000+'},
        _ => {},
      };

  void _selectFollowerBand(String value) {
    final range = switch (value) {
      'Under 1K' => const RangeValues(0, 999),
      '1K - 10K' => const RangeValues(1000, 10000),
      '10K - 50K' => const RangeValues(10000, 50000),
      '50K - 100K' => const RangeValues(50000, 100000),
      '100K - 500K' => const RangeValues(100000, 500000),
      '500K - 1M+' => const RangeValues(500000, 1000000),
      _ => _filters.followers,
    };
    setState(() => _filters = _filters.copyWith(followers: range));
  }

  void _selectUgcPriceBand(String value) {
    final range = switch (value) {
      'Under 500' => const RangeValues(0, 499),
      '500 - 1,000' => const RangeValues(500, 1000),
      '1,000 - 2,500' => const RangeValues(1000, 2500),
      '2,500 - 5,000' => const RangeValues(2500, 5000),
      '5,000 - 10,000' => const RangeValues(5000, 10000),
      '10,000+' => const RangeValues(10000, 10000),
      _ => _filters.price,
    };
    setState(() => _filters = _filters.copyWith(price: range));
  }

  String get _ugcAvailabilityLabel => switch (_filters.availability) {
        'week' => 'This Week',
        'month' => 'This Month',
        'custom' => 'Custom Date',
        _ => 'Any Time',
      };

  void _resetAll() {
    final cleared = _normalizeRanges(_clearedForType).copyWith(
      vendorSlug: _activeSlug.isEmpty ? null : _activeSlug,
    );
    _cityQuery.clear();
    Navigator.of(context).pop(cleared);
  }

  void _selectModelGender(String option) {
    var age = _filters.age;
    var height = _filters.height;
    if (option == 'Kids') {
      age = const RangeValues(9, 17);
      if (height.start >= 140) {
        height = const RangeValues(80, 160);
      }
    } else if (_filters.gender == 'Kids' || option == 'All') {
      if (age.end.round() <= 17) {
        age = const RangeValues(9, 50);
      }
      if (height.start.round() < 140) {
        height = const RangeValues(140, 190);
      }
    }
    setState(() => _filters = _filters.copyWith(gender: option, age: age, height: height));
  }

  Widget _genderTabs() {
    final options = ['All', ..._labels('model_identity', CategoryFilters.genders).where((item) => item != 'All')];
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: const Color(0xFF121316),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF22242A)),
      ),
      child: Row(
        children: options.map((option) {
          final selected = _filters.gender == option;
          return Expanded(
            child: GestureDetector(
              onTap: () => _selectModelGender(option),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 160),
                height: 46,
                decoration: BoxDecoration(
                  color: selected ? LensColors.primary : Colors.transparent,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      switch (option) {
                        'Kids' => Icons.escalator_warning_rounded,
                        'All' => Icons.groups_outlined,
                        _ => Icons.person_outline,
                      },
                      color: selected ? Colors.white : const Color(0xFFD0CBC3),
                      size: 16,
                    ),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        option,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: selected ? Colors.white : const Color(0xFFD0CBC3),
                          fontWeight: FontWeight.w700,
                          fontSize: 12.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _citySearch() {
    return TextField(
      controller: _cityQuery,
      onChanged: (_) => setState(() {}),
      style: const TextStyle(color: Colors.white, fontSize: 14),
      decoration: InputDecoration(
        hintText: 'Search city or area',
        hintStyle: const TextStyle(color: LensColors.slate, fontSize: 14),
        prefixIcon: const Icon(Icons.search, color: LensColors.slate, size: 20),
        filled: true,
        fillColor: const Color(0xFF141518),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: Color(0xFF2A2D34)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: LensColors.primary),
        ),
      ),
    );
  }

  Widget _sizeRow({
    required IconData icon,
    required String label,
    required List<String> options,
    required Set<String> selected,
    required ValueChanged<String> onTap,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, color: LensColors.cream, size: 16),
            const SizedBox(width: 6),
            Text(label, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13, fontWeight: FontWeight.w600)),
          ],
        ),
        const SizedBox(height: 8),
        _chips(options, selected, onTap),
      ],
    );
  }

  String _formatAmount(num value) {
    final digits = value.round().abs().toString();
    final buffer = StringBuffer(value < 0 ? '-' : '');
    for (var i = 0; i < digits.length; i++) {
      final fromEnd = digits.length - i;
      buffer.write(digits[i]);
      if (fromEnd > 1 && fromEnd % 3 == 1) {
        buffer.write(',');
      }
    }
    return buffer.toString();
  }

  String _formatCompact(num value) {
    final n = value.round();
    if (n >= 1000000) {
      final m = n / 1000000;
      return m == m.roundToDouble() ? '${m.round()}M' : '${m.toStringAsFixed(1)}M';
    }
    if (n >= 1000) {
      final k = n / 1000;
      return k == k.roundToDouble() ? '${k.round()}K' : '${k.toStringAsFixed(1)}K';
    }
    return _formatAmount(n);
  }

  String _priceTrailing(RangeValues values, {required double min, required double max}) {
    final safe = _clampRange(values, min, max);
    final endLabel = safe.end >= max - 0.001 ? '${_formatAmount(max)}+' : _formatAmount(safe.end);
    return 'EGP ${_formatAmount(safe.start)} – $endLabel';
  }

  RangeValues _clampRange(RangeValues values, double min, double max) {
    final start = values.start.clamp(min, max).toDouble();
    final end = values.end.clamp(min, max).toDouble();
    return RangeValues(start, end < start ? start : end);
  }

  int? _rangeDivisions(double min, double max) {
    final span = (max - min).abs();
    if (span <= 0) {
      return null;
    }
    if (span <= 60) {
      return span.round().clamp(1, 60);
    }
    if (span <= 500) {
      return (span / 5).round().clamp(20, 100);
    }
    if (span <= 2000) {
      return (span / 25).round().clamp(40, 120);
    }
    if (span <= 20000) {
      return (span / 50).round().clamp(80, 200);
    }
    return 100;
  }

  Widget _rangeSlider({
    required double min,
    required double max,
    required RangeValues values,
    required ValueChanged<RangeValues> onChanged,
    bool showLabels = true,
    String? unitPrefix,
    String? unitSuffix,
    bool openEndedMax = false,
    bool compact = false,
  }) {
    final safe = _clampRange(values, min, max);
    final divisions = _rangeDivisions(min, max);
    String labelFor(double value, {required bool isEnd}) {
      final raw = compact ? _formatCompact(value) : _formatAmount(value);
      final withPlus = openEndedMax && isEnd && value >= max - 0.001 ? '$raw+' : raw;
      if (unitPrefix != null) {
        return '$unitPrefix$withPlus';
      }
      if (unitSuffix != null) {
        return '$withPlus$unitSuffix';
      }
      return withPlus;
    }

    final startLabel = labelFor(safe.start, isEnd: false);
    final endLabel = labelFor(safe.end, isEnd: true);

    return Column(
      children: [
        if (showLabels)
          Padding(
            padding: const EdgeInsets.fromLTRB(2, 0, 2, 8),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFF1A120E),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0x55FF5A1F)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      startLabel,
                      key: ValueKey('range-start-$startLabel'),
                      style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                    ),
                  ),
                  const Text('—', style: TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w700)),
                  Expanded(
                    child: Text(
                      endLabel,
                      key: ValueKey('range-end-$endLabel'),
                      textAlign: TextAlign.right,
                      style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                    ),
                  ),
                ],
              ),
            ),
          ),
        SliderTheme(
          data: SliderTheme.of(context).copyWith(
            activeTrackColor: LensColors.primary,
            inactiveTrackColor: const Color(0xFF2A2D34),
            thumbColor: LensColors.primary,
            overlayColor: LensColors.primary.withValues(alpha: 0.18),
            rangeThumbShape: const RoundRangeSliderThumbShape(enabledThumbRadius: 10),
            rangeTrackShape: const RoundedRectRangeSliderTrackShape(),
            // Custom labels already render above the track; always-visible paddles
            // leak as orange dots at the top of the screen on this filter page.
            showValueIndicator: ShowValueIndicator.never,
            trackHeight: 4,
          ),
          child: RangeSlider(
            min: min,
            max: max,
            divisions: divisions,
            values: safe,
            onChanged: (next) {
              onChanged(_clampRange(next, min, max));
            },
          ),
        ),
      ],
    );
  }

  Widget _section({
    required String id,
    required IconData icon,
    required String title,
    required Widget child,
    String? trailing,
  }) {
    final open = _open.contains(id);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Container(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
        decoration: BoxDecoration(
          color: const Color(0xFF121316),
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0xFF22242A)),
        ),
        child: Column(
          children: [
            InkWell(
              onTap: () => setState(() => open ? _open.remove(id) : _open.add(id)),
              child: Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: const Color(0xFF1C1D22),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(icon, color: LensColors.primary, size: 18),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
                  ),
                  if (trailing != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: Text(trailing, style: const TextStyle(color: Color(0xFFE8E4DC), fontSize: 12, fontWeight: FontWeight.w700)),
                    ),
                  Icon(open ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded, color: LensColors.cream),
                ],
              ),
            ),
            if (open) ...[
              const SizedBox(height: 14),
              child,
            ],
          ],
        ),
      ),
    );
  }

  Widget _chips(
    List<String> options,
    Set<String> selected,
    ValueChanged<String> onTap, {
    bool showChevron = false,
    bool single = false,
  }) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: options.map((option) {
        final isOn = selected.contains(option);
        return GestureDetector(
          onTap: () {
            if (single) {
              onTap(option);
              return;
            }
            onTap(option);
          },
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 160),
            padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
            decoration: BoxDecoration(
              color: isOn ? LensColors.primary : const Color(0xFF1A1B1F),
              borderRadius: BorderRadius.circular(999),
              border: Border.all(color: isOn ? LensColors.primary : const Color(0xFF2A2D34)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  option,
                  style: TextStyle(
                    color: isOn ? Colors.white : const Color(0xFFD0CBC3),
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (isOn) ...[
                  const SizedBox(width: 4),
                  const Icon(Icons.check, color: Colors.white, size: 14),
                  if (showChevron) const Icon(Icons.expand_more_rounded, color: Colors.white, size: 16),
                ],
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _bottomButton({
    required String label,
    required Color background,
    required Color foreground,
    required VoidCallback onTap,
  }) {
    return Material(
      color: background,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: SizedBox(
          height: 54,
          child: Center(
            child: Text(label, style: TextStyle(color: foreground, fontWeight: FontWeight.w800, fontSize: 15)),
          ),
        ),
      ),
    );
  }
}
