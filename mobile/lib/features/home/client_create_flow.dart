import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/filter_page.dart';

Future<void> openClientCreateFlow(BuildContext context, HomeData home) async {
  final type = await showModalBottomSheet<VendorTypeItem>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    barrierColor: Colors.black.withValues(alpha: 0.72),
    builder: (_) => _CategoryPicker(home: home),
  );
  if (!context.mounted || type == null) {
    return;
  }

  await Navigator.of(context).push<void>(
    PageRouteBuilder<void>(
      transitionDuration: const Duration(milliseconds: 420),
      pageBuilder: (context, animation, secondaryAnimation) =>
          _RecommendationWizard(type: type, home: home),
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        final curve = CurvedAnimation(
          parent: animation,
          curve: Curves.easeOutCubic,
        );
        return FadeTransition(
          opacity: curve,
          child: SlideTransition(
            position: Tween<Offset>(
              begin: const Offset(0.08, 0),
              end: Offset.zero,
            ).animate(curve),
            child: child,
          ),
        );
      },
    ),
  );
}

class _CategoryPicker extends StatelessWidget {
  const _CategoryPicker({required this.home});

  final HomeData home;

  static const _categoryIcons = <String, IconData>{
    'photographer': Icons.photo_camera_outlined,
    'videographer': Icons.videocam_outlined,
    'reels': Icons.movie_creation_outlined,
    'studio': Icons.weekend_outlined,
    'model': Icons.face_retouching_natural_outlined,
    'ugc': Icons.phone_iphone_rounded,
    'food_stylist': Icons.restaurant_menu_rounded,
  };

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.86,
      minChildSize: 0.64,
      maxChildSize: 0.94,
      expand: false,
      builder: (context, controller) => Container(
        decoration: const BoxDecoration(
          color: Color(0xFF111216),
          borderRadius: BorderRadius.vertical(top: Radius.circular(30)),
          border: Border(top: BorderSide(color: Color(0x66FF5A1F))),
        ),
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFF55545A),
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(22, 22, 16, 14),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'LET’S MAKE IT HAPPEN',
                          style: TextStyle(
                            color: LensColors.primary,
                            fontSize: 10,
                            letterSpacing: 1.8,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 7),
                        const Text(
                          'What are you creating?',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 25,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 5),
                        Text(
                          'Choose a department and we’ll find the right fit.',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.62),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    tooltip: 'Close',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(
                      Icons.close_rounded,
                      color: LensColors.cream,
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: GridView.builder(
                controller: controller,
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 26),
                itemCount: home.vendorTypes.length,
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  mainAxisExtent: 126,
                ),
                itemBuilder: (context, index) {
                  final type = home.vendorTypes[index];
                  final icon =
                      _categoryIcons[type.slug] ?? Icons.auto_awesome_outlined;
                  return _CategoryTile(
                    type: type,
                    icon: icon,
                    onTap: () => Navigator.of(context).pop(type),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({
    required this.type,
    required this.icon,
    required this.onTap,
  });

  final VendorTypeItem type;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      key: Key('create-category-${type.slug}'),
      color: const Color(0xFF191A1F),
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Container(
          padding: const EdgeInsets.all(15),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: const Color(0xFF2B2C32)),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF202126), Color(0xFF151619)],
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: LensColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: LensColors.primary, size: 21),
              ),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      type.label,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const Icon(
                    Icons.arrow_forward_rounded,
                    color: LensColors.primary,
                    size: 17,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

enum _StepKind {
  gender,
  service,
  content,
  cuisine,
  equipment,
  location,
  budget,
  rating,
}

class _WizardStep {
  const _WizardStep(this.kind, this.title, this.description, this.icon);

  final _StepKind kind;
  final String title;
  final String description;
  final IconData icon;
}

class _RecommendationWizard extends StatefulWidget {
  const _RecommendationWizard({required this.type, required this.home});

  final VendorTypeItem type;
  final HomeData home;

  @override
  State<_RecommendationWizard> createState() => _RecommendationWizardState();
}

class _RecommendationWizardState extends State<_RecommendationWizard> {
  late final List<_WizardStep> _steps;
  late CategoryFilters _filters;
  int _stepIndex = 0;

  String get _slug => widget.type.slug;
  _WizardStep get _step => _steps[_stepIndex];

  @override
  void initState() {
    super.initState();
    _filters = CategoryFilters(gender: _slug == 'model' ? 'All' : 'Men');
    _steps = _makeSteps();
  }

  List<_WizardStep> _makeSteps() {
    final steps = <_WizardStep>[];
    if (_slug == 'model') {
      steps.add(
        const _WizardStep(
          _StepKind.gender,
          'Who are you looking for?',
          'Choose the talent that fits your vision.',
          Icons.face_retouching_natural_outlined,
        ),
      );
    }
    steps.add(
      _WizardStep(
        _StepKind.service,
        _serviceTitle,
        _serviceDescription,
        _serviceIcon,
      ),
    );
    if (_slug == 'ugc') {
      steps.add(
        const _WizardStep(
          _StepKind.content,
          'What should they create?',
          'Select the content formats you need.',
          Icons.movie_filter_outlined,
        ),
      );
    } else if (_slug == 'food_stylist') {
      steps.add(
        const _WizardStep(
          _StepKind.cuisine,
          'Which cuisine?',
          'Find a specialist for the food story you have in mind.',
          Icons.restaurant_outlined,
        ),
      );
    } else if (_slug == 'studio') {
      steps.add(
        const _WizardStep(
          _StepKind.equipment,
          'What should the space include?',
          'Pick any must-have studio features.',
          Icons.tune_rounded,
        ),
      );
    }
    steps
      ..add(
        const _WizardStep(
          _StepKind.location,
          'Where should it happen?',
          'We’ll prioritize creatives close to you.',
          Icons.location_on_outlined,
        ),
      )
      ..add(
        const _WizardStep(
          _StepKind.budget,
          'What’s your budget?',
          'Choose a comfortable starting range.',
          Icons.payments_outlined,
        ),
      )
      ..add(
        const _WizardStep(
          _StepKind.rating,
          'What matters most?',
          'Fine-tune the quality of your recommendations.',
          Icons.auto_awesome_outlined,
        ),
      );
    return steps;
  }

  String get _serviceTitle => switch (_slug) {
    'model' => 'What kind of talent do you need?',
    'studio' => 'What kind of space do you need?',
    'ugc' => 'What kind of creator do you need?',
    'food_stylist' => 'What kind of food expertise?',
    _ => 'What are you creating?',
  };

  String get _serviceDescription => switch (_slug) {
    'model' => 'Choose one or more creative directions.',
    'studio' => 'Choose the space that brings your idea to life.',
    'ugc' => 'Pick the creator categories that fit your brand.',
    'food_stylist' => 'Select the skills your project calls for.',
    _ => 'Pick one or more types of work.',
  };

  IconData get _serviceIcon => switch (_slug) {
    'model' => Icons.style_outlined,
    'studio' => Icons.weekend_outlined,
    'ugc' => Icons.lightbulb_outline_rounded,
    'food_stylist' => Icons.restaurant_menu_rounded,
    _ => Icons.camera_alt_outlined,
  };

  List<String> get _cities {
    final values = widget.home.cities
        .map((city) => city['name']?.toString() ?? '')
        .where((name) => name.isNotEmpty)
        .toSet()
        .toList();
    return values.isEmpty
        ? CategoryFilters.cityOptions.take(12).toList()
        : values;
  }

  List<String> get _options => switch (_step.kind) {
    _StepKind.gender => const ['All', ...CategoryFilters.genders],
    _StepKind.service => switch (_slug) {
      'model' => _catalogOptions(
        'model_category',
        CategoryFilters.modelCategories,
      ),
      'studio' => _catalogOptions(
        'studio_type',
        CategoryFilters.studioTypes,
      ).where((item) => item != 'All Types').toList(),
      'ugc' => _catalogOptions(
        'ugc_niche',
        CategoryFilters.ugcNiches,
      ).where((item) => item != 'All Categories').toList(),
      'food_stylist' => _catalogOptions(
        'food_expertise',
        CategoryFilters.foodExpertise,
      ).where((item) => item != 'All Expertise').toList(),
      _ => CategoryFilters.serviceOptions,
    },
    _StepKind.content => _catalogOptions(
      'ugc_content',
      CategoryFilters.ugcContentTypes,
    ).where((item) => item != 'All Types').toList(),
    _StepKind.cuisine => _catalogOptions(
      'food_cuisine',
      CategoryFilters.foodCuisines,
    ).where((item) => item != 'All Cuisines').toList(),
    _StepKind.equipment => _catalogOptions(
      'studio_features',
      CategoryFilters.studioEquipment,
    ).where((item) => item != 'All Equipment').toList(),
    _StepKind.location => ['Anywhere', ..._cities],
    _StepKind.budget => _budgetOptions,
    _StepKind.rating => const ['Any rating', '4.0+', '4.5+', '4.8+'],
  };

  List<String> get _budgetOptions => _slug == 'studio'
      ? const [
          'Any budget',
          'Under EGP 500',
          'EGP 500–1,000',
          'EGP 1,000–2,000',
        ]
      : const [
          'Any budget',
          'Under EGP 1,000',
          'EGP 1,000–2,500',
          'EGP 2,500–5,000',
          'EGP 5,000+',
        ];

  List<String> _catalogOptions(String slug, List<String> fallback) {
    for (final group in widget.home.filterCatalog) {
      if (group.slug == slug && group.options.isNotEmpty) {
        return group.options.map((option) => option.label).toList();
      }
    }
    return fallback;
  }

  Set<String> get _selected => switch (_step.kind) {
    _StepKind.gender => {_filters.gender},
    _StepKind.service => _filters.services,
    _StepKind.content => _filters.contentTypes,
    _StepKind.cuisine => _filters.accents,
    _StepKind.equipment => _filters.equipment,
    _StepKind.location =>
      _filters.cities.isEmpty ? {'Anywhere'} : _filters.cities,
    _StepKind.budget => {_budgetLabel},
    _StepKind.rating => {_ratingLabel},
  };

  String get _budgetLabel {
    final range = _filters.price;
    if (range.start == 0 && range.end >= (_slug == 'studio' ? 2000 : 10000)) {
      return 'Any budget';
    }
    if (_slug == 'studio') {
      if (range.end <= 500) return 'Under EGP 500';
      if (range.start >= 500 && range.end <= 1000) return 'EGP 500–1,000';
      return 'EGP 1,000–2,000';
    }
    if (range.end <= 1000) return 'Under EGP 1,000';
    if (range.start >= 1000 && range.end <= 2500) return 'EGP 1,000–2,500';
    if (range.start >= 2500 && range.end <= 5000) return 'EGP 2,500–5,000';
    return 'EGP 5,000+';
  }

  String get _ratingLabel => switch (_filters.minRating) {
    4.0 => '4.0+',
    4.5 => '4.5+',
    4.8 => '4.8+',
    _ => 'Any rating',
  };

  bool get _multiSelect => {
    _StepKind.service,
    _StepKind.content,
    _StepKind.cuisine,
    _StepKind.equipment,
  }.contains(_step.kind);

  void _select(String option) {
    setState(() {
      switch (_step.kind) {
        case _StepKind.gender:
          _filters = _filters.copyWith(gender: option);
        case _StepKind.service:
          _filters = _filters.copyWith(
            services: _toggleSet(_filters.services, option),
          );
        case _StepKind.content:
          _filters = _filters.copyWith(
            contentTypes: _toggleSet(_filters.contentTypes, option),
          );
        case _StepKind.cuisine:
          _filters = _filters.copyWith(
            accents: _toggleSet(_filters.accents, option),
          );
        case _StepKind.equipment:
          _filters = _filters.copyWith(
            equipment: _toggleSet(_filters.equipment, option),
          );
        case _StepKind.location:
          _filters = _filters.copyWith(
            cities: option == 'Anywhere' ? {} : {option},
          );
        case _StepKind.budget:
          _filters = _filters.copyWith(price: _budgetRange(option));
        case _StepKind.rating:
          _filters = switch (option) {
            '4.0+' => _filters.copyWith(minRating: 4.0),
            '4.5+' => _filters.copyWith(minRating: 4.5),
            '4.8+' => _filters.copyWith(minRating: 4.8),
            _ => _filters.copyWith(clearRating: true),
          };
      }
    });
  }

  Set<String> _toggleSet(Set<String> current, String value) {
    final next = {...current};
    if (!next.add(value)) {
      next.remove(value);
    }
    return next;
  }

  RangeValues _budgetRange(String option) {
    if (option == 'Any budget') {
      return RangeValues(0, _slug == 'studio' ? 2000 : 10000);
    }
    return switch (option) {
      'Under EGP 500' => const RangeValues(0, 500),
      'EGP 500–1,000' => const RangeValues(500, 1000),
      'EGP 1,000–2,000' => const RangeValues(1000, 2000),
      'Under EGP 1,000' => const RangeValues(0, 1000),
      'EGP 1,000–2,500' => const RangeValues(1000, 2500),
      'EGP 2,500–5,000' => const RangeValues(2500, 5000),
      _ => const RangeValues(5000, 10000),
    };
  }

  void _continue() {
    if (_stepIndex < _steps.length - 1) {
      setState(() => _stepIndex++);
      return;
    }

    Navigator.of(context).pushReplacement<void, void>(
      MaterialPageRoute<void>(
        builder: (_) => CategoryListPage(
          type: widget.type,
          home: widget.home,
          initialFilters: _filters,
          showRecommendations: true,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final progress = (_stepIndex + 1) / _steps.length;
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 20, 18),
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Back',
                    onPressed: _stepIndex == 0
                        ? () => Navigator.of(context).pop()
                        : () => setState(() => _stepIndex--),
                    icon: const Icon(
                      Icons.arrow_back_rounded,
                      color: LensColors.cream,
                    ),
                  ),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          widget.type.label.toUpperCase(),
                          style: const TextStyle(
                            color: LensColors.primary,
                            fontSize: 10,
                            letterSpacing: 1.8,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'STEP ${_stepIndex + 1} OF ${_steps.length}',
                          style: const TextStyle(
                            color: LensColors.slate,
                            fontSize: 10,
                            letterSpacing: 1.2,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                  TextButton(
                    onPressed: _showAllFilters,
                    child: const Text(
                      'More filters',
                      style: TextStyle(color: LensColors.cream, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 22),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: LinearProgressIndicator(
                  value: progress,
                  minHeight: 4,
                  backgroundColor: const Color(0xFF292A30),
                  valueColor: const AlwaysStoppedAnimation(LensColors.primary),
                ),
              ),
            ),
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 250),
                child: ListView(
                  key: ValueKey(_stepIndex),
                  padding: const EdgeInsets.fromLTRB(22, 30, 22, 18),
                  children: [
                    Container(
                      width: 54,
                      height: 54,
                      decoration: BoxDecoration(
                        color: LensColors.primary.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(18),
                      ),
                      child: Icon(
                        _step.icon,
                        color: LensColors.primary,
                        size: 25,
                      ),
                    ),
                    const SizedBox(height: 22),
                    Text(
                      _step.title,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 28,
                        height: 1.15,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 9),
                    Text(
                      _step.description,
                      style: const TextStyle(
                        color: Color(0xFF9D9BA2),
                        fontSize: 14,
                        height: 1.45,
                      ),
                    ),
                    const SizedBox(height: 26),
                    ..._options.map(_optionCard),
                  ],
                ),
              ),
            ),
            Padding(
              padding: EdgeInsets.fromLTRB(
                22,
                10,
                22,
                16 + MediaQuery.paddingOf(context).bottom,
              ),
              child: SizedBox(
                height: 56,
                width: double.infinity,
                child: FilledButton(
                  onPressed: _continue,
                  style: FilledButton.styleFrom(
                    backgroundColor: LensColors.primary,
                    foregroundColor: LensColors.charcoal,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(18),
                    ),
                    textStyle: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 15,
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        _stepIndex == _steps.length - 1
                            ? 'Show my recommendations'
                            : 'Continue',
                      ),
                      const SizedBox(width: 9),
                      const Icon(Icons.arrow_forward_rounded, size: 19),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _optionCard(String option) {
    final selected = _selected.contains(option);
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        key: Key('create-option-${_step.kind.name}-${_keyPart(option)}'),
        color: selected ? const Color(0xFF2A170F) : const Color(0xFF17181C),
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          onTap: () => _select(option),
          borderRadius: BorderRadius.circular(18),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected ? LensColors.primary : const Color(0xFF2B2C32),
                width: selected ? 1.3 : 1,
              ),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    option,
                    style: TextStyle(
                      color: selected ? Colors.white : const Color(0xFFD6D2CC),
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                    ),
                  ),
                ),
                Icon(
                  selected
                      ? Icons.check_circle_rounded
                      : (_multiSelect
                            ? Icons.add_circle_outline_rounded
                            : Icons.circle_outlined),
                  color: selected
                      ? LensColors.primary
                      : const Color(0xFF67666D),
                  size: 21,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String _keyPart(String value) =>
      value.toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '-');

  Future<void> _showAllFilters() async {
    final result = await Navigator.of(context).push<CategoryFilters>(
      MaterialPageRoute<CategoryFilters>(
        builder: (_) => FilterPage(
          title: widget.type.label,
          vendorTypeSlug: widget.type.slug,
          initial: _filters,
          catalog: widget.home.filterCatalog,
        ),
      ),
    );
    if (result != null && mounted) {
      setState(() => _filters = result);
    }
  }
}
