import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/category_catalog.dart';
import 'package:lens/features/shell/app_shell.dart';

void openAllCategories(BuildContext context, HomeData home) {
  Navigator.of(context).push(
    MaterialPageRoute<void>(builder: (_) => CategoriesPage(home: home)),
  );
}

class CategoriesPage extends StatefulWidget {
  const CategoriesPage({super.key, required this.home});

  final HomeData home;

  @override
  State<CategoriesPage> createState() => _CategoriesPageState();
}

class _CategoriesPageState extends State<CategoriesPage> {
  late HomeData _home;

  @override
  void initState() {
    super.initState();
    _home = widget.home;
    _refresh();
  }

  Future<void> _refresh() async {
    if (!LensConfig.useNetwork) {
      return;
    }
    try {
      final remote = await ApiClient().getJson('/app/bootstrap');
      final types = remote['vendor_types'];
      if (!mounted || types is! List || types.isEmpty) {
        return;
      }
      final previous = _home.vendorTypes;
      final previousSlugs = {for (final type in previous) type.slug};
      final remoteSlugs = {
        for (final item in types)
          if (item is Map && '${item['slug'] ?? ''}'.trim().isNotEmpty)
            '${item['slug']}'.trim(),
      };
      final merged = Map<String, dynamic>.from(remote);
      // Never shrink or reshuffle an already-full grid on this screen.
      if (remoteSlugs.difference(previousSlugs).isEmpty && previous.isNotEmpty) {
        merged['vendor_types'] = [
          for (final type in previous)
            {
              'slug': type.slug,
              'label': type.label,
              if (type.icon != null) 'icon': type.icon,
            },
        ];
      }
      final home = HomeData.fromJson(merged);
      AppShell.currentHome = home;
      AppShell.currentBootstrap = merged;
      setState(() => _home = home);
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    final categories = CategoryCatalog.entries(_home.vendorTypes);

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      bottomNavigationBar: AppShell.navBar(context, index: 0),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(4, 4, 16, 8),
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Back',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(
                      Icons.chevron_left_rounded,
                      color: LensColors.cream,
                      size: 30,
                    ),
                  ),
                  const Expanded(
                    child: Text(
                      'All Categories',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(10, 4, 10, 96),
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
                  if (categories.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 48),
                      child: Text(
                        'No vendor types are enabled in the admin panel yet.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13.5),
                      ),
                    )
                  else
                    CategoryGrid(
                      categories: categories,
                      keyPrefix: 'all-category',
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      onOpen: (category) => CategoryCatalog.open(
                        context,
                        home: _home,
                        category: category,
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
}
