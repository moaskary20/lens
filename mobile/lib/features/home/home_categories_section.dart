import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/categories_page.dart';
import 'package:lens/features/home/category_catalog.dart';

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
    final categories = CategoryCatalog.entries(types);

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
          CategoryGrid(
            categories: categories,
            keyPrefix: 'home-category',
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            onOpen: (category) => CategoryCatalog.open(context, home: home, category: category),
          ),
        ],
      ),
    );
  }
}
