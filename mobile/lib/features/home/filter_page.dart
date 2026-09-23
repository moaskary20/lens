import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';

class CategoryFilters {
  const CategoryFilters({
    this.gender = 'Men',
    this.services = const {},
    this.cities = const {},
    this.age = const RangeValues(18, 50),
    this.height = const RangeValues(160, 200),
    this.tops = const {},
    this.pants = const {},
    this.shoes = const {},
    this.equipment = const {},
    this.contentTypes = const {},
    this.accents = const {},
    this.languages = const {},
    this.ageGroups = const {},
    this.studioSize = const RangeValues(50, 500),
    this.followers = const RangeValues(1000, 1000000),
    this.price = const RangeValues(0, 10000),
    this.minRating,
    this.availability = 'any',
    this.verifiedOnly = false,
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

  static const ageBands = ['Under 18', '18 - 25', '26 - 35', '36 - 50', '50+'];

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
  static const shoeSizes = ['39', '40', '41', '42', '43', '44', '45+'];

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
    age: RangeValues(18, 50),
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
    );
  }

  bool get isEmpty =>
      services.isEmpty &&
      cities.isEmpty &&
      tops.isEmpty &&
      pants.isEmpty &&
      shoes.isEmpty &&
      equipment.isEmpty &&
      contentTypes.isEmpty &&
      accents.isEmpty &&
      languages.isEmpty &&
      ageGroups.isEmpty &&
      price.start == 0 &&
      price.end == 10000 &&
      minRating == null &&
      availability == 'any' &&
      !verifiedOnly;
}

class FilterPage extends StatefulWidget {
  const FilterPage({
    super.key,
    required this.title,
    this.vendorTypeSlug,
    this.initial = const CategoryFilters(),
    this.catalog = const [],
  });

  final String title;
  final String? vendorTypeSlug;
  final CategoryFilters initial;
  final List<FilterGroupItem> catalog;

  @override
  State<FilterPage> createState() => _FilterPageState();
}

class _FilterPageState extends State<FilterPage> {
  late CategoryFilters _filters;
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

  bool get _isModel => (widget.vendorTypeSlug ?? '').toLowerCase() == 'model' || widget.title.toLowerCase().contains('model');

  bool get _isStudio => (widget.vendorTypeSlug ?? '').toLowerCase() == 'studio' || widget.title.toLowerCase().contains('studio');

  bool get _isUgc => (widget.vendorTypeSlug ?? '').toLowerCase() == 'ugc' || widget.title.toLowerCase().contains('ugc');

  bool get _isFood =>
      (widget.vendorTypeSlug ?? '').toLowerCase() == 'food_stylist' || widget.title.toLowerCase().contains('food');

  @override
  void initState() {
    super.initState();
    _filters = widget.initial.isEmpty ? _previewForType : widget.initial;
    _cityQuery = TextEditingController();
  }

  CategoryFilters get _previewForType {
    if (_isModel) {
      return CategoryFilters.modelPreview;
    }
    if (_isStudio) {
      return CategoryFilters.studioPreview;
    }
    if (_isUgc) {
      return CategoryFilters.ugcPreview;
    }
    if (_isFood) {
      return CategoryFilters.foodPreview;
    }
    return const CategoryFilters();
  }

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

  @override
  Widget build(BuildContext context) {
    final title = _isModel ? 'Filter ${widget.title} (${_filters.gender})' : 'Filter ${widget.title}';

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(20, 6, 20, 0),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text.rich(
                      TextSpan(
                        children: [
                          TextSpan(
                            text: 'Lens',
                            style: TextStyle(color: LensColors.primary, fontSize: 28, fontWeight: FontWeight.w800, height: 1),
                          ),
                          TextSpan(
                            text: '.',
                            style: TextStyle(color: LensColors.primary, fontSize: 28, fontWeight: FontWeight.w800, height: 1),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'FIND. BOOK. CREATE.',
                      style: TextStyle(color: LensColors.slate, fontSize: 9, letterSpacing: 1.6, fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 16, 8),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
                  ),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800),
                    ),
                  ),
                  TextButton(
                    onPressed: () => setState(() {
                      _filters = _previewForType;
                      _cityQuery.clear();
                    }),
                    child: const Text('Reset All', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                cacheExtent: 2000,
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
                children: [
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
                            min: 0,
                            max: 60,
                            values: _filters.age,
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(age: value)),
                          ),
                          _chips(
                            _labels('model_age', CategoryFilters.ageBands),
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
                            min: 140,
                            max: 210,
                            values: _filters.height,
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
                      trailing: 'EGP ${_filters.price.start.round()} - ${_filters.price.end.round()}+',
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: 0,
                            max: 2000,
                            values: RangeValues(_filters.price.start.clamp(0, 2000), _filters.price.end.clamp(0, 2000)),
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
                      child: Column(
                        children: [
                          _rangeSlider(
                            min: _isModel ? 500 : 0,
                            max: 10000,
                            values: RangeValues(
                              _isModel ? _filters.price.start.clamp(500, 10000) : _filters.price.start,
                              _filters.price.end,
                            ),
                            onChanged: (value) => setState(() => _filters = _filters.copyWith(price: value)),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            child: Row(
                              children: [
                                Text(
                                  _isModel ? '500' : '0',
                                  style: const TextStyle(color: LensColors.slate, fontSize: 12, fontWeight: FontWeight.w600),
                                ),
                                const Spacer(),
                                const Text('10,000+', style: TextStyle(color: LensColors.slate, fontSize: 12, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                        ],
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
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
              child: Row(
                children: [
                  Expanded(
                    child: _bottomButton(
                      label: 'Cancel',
                      background: const Color(0xFF1A1B1F),
                      foreground: Colors.white,
                      onTap: () => Navigator.of(context).pop(),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _bottomButton(
                      label: 'Apply Filters',
                      background: LensColors.primary,
                      foreground: Colors.white,
                      onTap: () => Navigator.of(context).pop(_filters),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
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
        (0, 17) => {'Under 18'},
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
      'Under 18' => const RangeValues(0, 17),
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
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
        decoration: BoxDecoration(
          color: const Color(0xFF121316),
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0xFF22242A)),
        ),
        child: Row(
          children: [
            const Icon(Icons.verified_outlined, color: LensColors.cream, size: 20),
            const SizedBox(width: 8),
            const Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Verified Only', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
                  SizedBox(height: 2),
                  Text('Show only verified studios', style: TextStyle(color: LensColors.slate, fontSize: 12)),
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
        trailing: 'EGP ${_filters.price.start.round()} - 10,000+',
        child: Column(
          children: [
            _rangeSlider(
              min: 0,
              max: 10000,
              values: RangeValues(_filters.price.start.clamp(0, 10000), _filters.price.end.clamp(0, 10000)),
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
        trailing: 'EGP ${_filters.price.start.round()} - ${_filters.price.end >= 10000 ? '10,000+' : _filters.price.end.round().toString()}',
        child: Column(
          children: [
            _rangeSlider(
              min: 0,
              max: 10000,
              values: RangeValues(_filters.price.start.clamp(0, 10000), _filters.price.end.clamp(0, 10000)),
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

  Widget _genderTabs() {
    final options = _labels('model_identity', CategoryFilters.genders);
    return Row(
      children: options.map((option) {
        final selected = _filters.gender == option;
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(right: option == options.last ? 0 : 8),
            child: GestureDetector(
              onTap: () => setState(() => _filters = _filters.copyWith(gender: option)),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 160),
                height: 48,
                decoration: BoxDecoration(
                  color: selected ? const Color(0xFF2A140E) : const Color(0xFF141518),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2D34)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      option == 'Kids' ? Icons.escalator_warning_rounded : Icons.person_outline,
                      color: selected ? LensColors.primary : LensColors.cream,
                      size: 18,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      option,
                      style: TextStyle(
                        color: selected ? Colors.white : const Color(0xFFD0CBC3),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      }).toList(),
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

  Widget _rangeSlider({
    required double min,
    required double max,
    required RangeValues values,
    required ValueChanged<RangeValues> onChanged,
  }) {
    final start = values.start.clamp(min, max);
    final end = values.end.clamp(min, max);
    return SliderTheme(
      data: SliderTheme.of(context).copyWith(
        activeTrackColor: LensColors.primary,
        inactiveTrackColor: const Color(0xFF2A2D34),
        thumbColor: LensColors.primary,
        overlayColor: LensColors.primary.withValues(alpha: 0.16),
        rangeThumbShape: const RoundRangeSliderThumbShape(enabledThumbRadius: 8),
        rangeTrackShape: const RoundedRectRangeSliderTrackShape(),
        trackHeight: 4,
      ),
      child: RangeSlider(
        min: min,
        max: max,
        values: RangeValues(start, end < start ? start : end),
        onChanged: onChanged,
      ),
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
                  Icon(icon, color: LensColors.cream, size: 20),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700)),
                  ),
                  if (trailing != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 6),
                      child: Text(trailing, style: const TextStyle(color: LensColors.slate, fontSize: 12, fontWeight: FontWeight.w600)),
                    ),
                  Icon(open ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded, color: LensColors.cream),
                ],
              ),
            ),
            if (open) ...[
              const SizedBox(height: 12),
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
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
            decoration: BoxDecoration(
              color: isOn ? const Color(0xFF2A140E) : const Color(0xFF141518),
              borderRadius: BorderRadius.circular(20),
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
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (isOn) ...[
                  const SizedBox(width: 4),
                  const Icon(Icons.check, color: LensColors.primary, size: 14),
                  if (showChevron) const Icon(Icons.expand_more_rounded, color: LensColors.primary, size: 16),
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
          height: 52,
          child: Center(
            child: Text(label, style: TextStyle(color: foreground, fontWeight: FontWeight.w800, fontSize: 15)),
          ),
        ),
      ),
    );
  }
}
