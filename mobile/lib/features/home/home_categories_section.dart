import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/categories_page.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/filter_page.dart';

class HomeCategoriesSection extends StatelessWidget {
  const HomeCategoriesSection({
    super.key,
    required this.types,
    required this.home,
  });

  final List<VendorTypeItem> types;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    final categories = _categories();

    return Padding(
      padding: const EdgeInsets.fromLTRB(10, 0, 10, 8),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(2, 0, 2, 6),
            child: Row(
              children: [
                const Expanded(
                  child: Text(
                    'Categories',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                GestureDetector(
                  key: const Key('view-all-categories'),
                  onTap: () => openAllCategories(context, home),
                  child: const Text(
                    'View all',
                    style: TextStyle(
                      color: LensColors.primary,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: categories.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              mainAxisSpacing: 6,
              crossAxisSpacing: 6,
              mainAxisExtent: 60,
            ),
            itemBuilder: (context, index) {
              final category = categories[index];
              return _CategoryTile(
                category: category,
                onTap: () => _open(context, category),
              );
            },
          ),
        ],
      ),
    );
  }

  List<_HomeCategory> _categories() {
    final bySlug = {for (final type in types) type.slug: type};
    final definitions = <_CategoryDefinition>[
      _CategoryDefinition(
        'photographer',
        'Photographers',
        Icons.photo_camera_outlined,
      ),
      _CategoryDefinition(
        'videographer',
        'Videographers',
        Icons.videocam_outlined,
      ),
      _CategoryDefinition('reels', 'Mobile Reels', Icons.phone_iphone),
      _CategoryDefinition('model', 'Models', Icons.person_outline),
      _CategoryDefinition('studio', 'Studios', Icons.home_outlined),
      _CategoryDefinition('ugc', 'UGC Creators', Icons.groups_2_outlined),
      _CategoryDefinition('food_stylist', 'Food Stylists', Icons.restaurant),
      _CategoryDefinition(
        'ai_video',
        'AI Videos & Motion',
        Icons.smart_display_outlined,
        targetSlug: 'videographer',
      ),
      _CategoryDefinition(
        'voiceover',
        'VO (Voice Over)',
        Icons.mic_none_rounded,
        targetSlug: 'ugc',
        filters: const CategoryFilters(contentTypes: {'Voiceover'}),
      ),
    ];

    return definitions
        .map((definition) {
          final targetSlug = definition.targetSlug ?? definition.slug;
          final type =
              bySlug[targetSlug] ??
              switch (definition.slug) {
                'ai_video' => const VendorTypeItem(
                  slug: 'videographer',
                  label: 'Videographers',
                ),
                'voiceover' => const VendorTypeItem(
                  slug: 'ugc',
                  label: 'UGC Creators',
                ),
                _ => null,
              };
          return type == null
              ? null
              : _HomeCategory(definition: definition, type: type);
        })
        .whereType<_HomeCategory>()
        .toList();
  }

  void _open(BuildContext context, _HomeCategory category) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => CategoryListPage(
          type: category.type,
          home: home,
          displayTitle: category.definition.label,
          initialFilters: category.definition.filters,
          vendors: home.popular
              .where((section) => section.slug == category.type.slug)
              .expand((section) => section.vendors)
              .toList(),
        ),
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({required this.category, required this.onTap});

  final _HomeCategory category;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFF141518),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        key: Key('home-category-${category.definition.slug}'),
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFF22242A)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 3, vertical: 4),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                category.definition.icon,
                color: LensColors.primary,
                size: 23,
              ),
              const SizedBox(height: 2),
              Text(
                category.definition.label,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: LensColors.cream,
                  fontSize: 10,
                  height: 1.05,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CategoryDefinition {
  const _CategoryDefinition(
    this.slug,
    this.label,
    this.icon, {
    this.targetSlug,
    this.filters,
  });

  final String slug;
  final String label;
  final IconData icon;
  final String? targetSlug;
  final CategoryFilters? filters;
}

class _HomeCategory {
  const _HomeCategory({required this.definition, required this.type});

  final _CategoryDefinition definition;
  final VendorTypeItem type;
}
