import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/filter_page.dart';

class HomeCategoryEntry {
  const HomeCategoryEntry({
    required this.slug,
    required this.label,
    required this.icon,
    required this.type,
    this.filters,
  });

  final String slug;
  final String label;
  final IconData icon;
  final VendorTypeItem type;
  final CategoryFilters? filters;
}

class CategoryCatalog {
  const CategoryCatalog._();

  /// Exact Vendor Types from admin (`/api/app/bootstrap` → `vendor_types`).
  static List<HomeCategoryEntry> entries(List<VendorTypeItem> types) {
    final seen = <String>{};
    return [
      for (final type in types)
        if (type.slug.isNotEmpty && seen.add(type.slug))
          HomeCategoryEntry(
            slug: type.slug,
            label: type.label.isNotEmpty ? type.label : type.slug,
            icon: iconFor(type.slug, type.icon),
            type: type,
          ),
    ];
  }

  static IconData iconFor(String slug, [String? remote]) {
    final mapped = switch (slug) {
      'photographer' => Icons.photo_camera_outlined,
      'videographer' => Icons.videocam_outlined,
      'reels' => Icons.phone_iphone,
      'model' => Icons.person_outline,
      'studio' => Icons.home_outlined,
      'ugc' => Icons.groups_2_outlined,
      'food_stylist' => Icons.restaurant,
      'ai_video' => Icons.smart_display_outlined,
      'voiceover' => Icons.mic_none_rounded,
      _ => null,
    };
    if (mapped != null) {
      return mapped;
    }
    return _iconFromName(remote) ?? Icons.category_outlined;
  }

  static IconData? _iconFromName(String? name) {
    if (name == null || name.trim().isEmpty) {
      return null;
    }
    final key = name.trim().toLowerCase().replaceAll('-', '_').replaceAll(' ', '_');
    return switch (key) {
      'camera' || 'photo_camera' || 'photo' => Icons.photo_camera_outlined,
      'video' || 'videocam' || 'film' => Icons.videocam_outlined,
      'phone' || 'mobile' || 'reels' => Icons.phone_iphone,
      'person' || 'user' || 'model' => Icons.person_outline,
      'home' || 'studio' || 'building' => Icons.home_outlined,
      'users' || 'group' || 'groups' || 'ugc' => Icons.groups_2_outlined,
      'restaurant' || 'food' || 'utensils' => Icons.restaurant,
      'mic' || 'microphone' || 'voice' => Icons.mic_none_rounded,
      'sparkles' || 'ai' || 'display' => Icons.smart_display_outlined,
      _ => null,
    };
  }

  static void open(BuildContext context, {required HomeData home, required HomeCategoryEntry category}) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => CategoryListPage(
          type: category.type,
          home: home,
          displayTitle: category.label,
          initialFilters: category.filters,
          vendors: home.popular
              .where((section) => section.slug == category.type.slug)
              .expand((section) => section.vendors)
              .toList(),
        ),
      ),
    );
  }
}

class CategoryTile extends StatelessWidget {
  const CategoryTile({
    super.key,
    required this.category,
    required this.onTap,
  });

  final HomeCategoryEntry category;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFF141518),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
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
              Icon(category.icon, color: LensColors.primary, size: 23),
              const SizedBox(height: 2),
              Text(
                category.label,
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

class CategoryGrid extends StatelessWidget {
  const CategoryGrid({
    super.key,
    required this.categories,
    required this.onOpen,
    this.shrinkWrap = false,
    this.physics,
    this.keyPrefix = 'category',
  });

  final List<HomeCategoryEntry> categories;
  final ValueChanged<HomeCategoryEntry> onOpen;
  final bool shrinkWrap;
  final ScrollPhysics? physics;
  final String keyPrefix;

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: shrinkWrap,
      physics: physics,
      itemCount: categories.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 4,
        mainAxisSpacing: 6,
        crossAxisSpacing: 6,
        mainAxisExtent: 60,
      ),
      itemBuilder: (context, index) {
        final category = categories[index];
        return CategoryTile(
          key: Key('$keyPrefix-${category.slug}'),
          category: category,
          onTap: () => onOpen(category),
        );
      },
    );
  }
}
