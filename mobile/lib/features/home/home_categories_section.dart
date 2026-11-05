import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Padding(
            padding: EdgeInsets.fromLTRB(2, 0, 2, 6),
            child: Text(
              'Categories',
              style: TextStyle(
                color: Colors.white,
                fontSize: 14,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          LayoutBuilder(
            builder: (context, constraints) {
              const columns = 4;
              const gap = 6.0;
              const tileHeight = 60.0;
              final width = constraints.maxWidth;
              final tileWidth = (width - (gap * (columns - 1))) / columns;
              return Wrap(
                spacing: gap,
                runSpacing: gap,
                children: [
                  for (final category in categories)
                    SizedBox(
                      width: tileWidth,
                      height: tileHeight,
                      child: CategoryTile(
                        key: Key('home-category-${category.slug}'),
                        category: category,
                        onTap: () => CategoryCatalog.open(context, home: home, category: category),
                      ),
                    ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}
