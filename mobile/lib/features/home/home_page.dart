import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/core/widgets/brand_logo.dart';
import 'package:lens/features/home/ai_search_page.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/home_categories_section.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/filter_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/vendor_profile_page.dart';

class HomePage extends StatelessWidget {
  const HomePage({
    super.key,
    required this.data,
    required this.onOpenMenu,
    required this.onOpenSearch,
  });

  final HomeData data;
  final VoidCallback onOpenMenu;
  final VoidCallback onOpenSearch;

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: SessionStore.instance,
      builder: (context, _) => SafeArea(
        child: CustomScrollView(
          cacheExtent: 1200,
          slivers: [
            SliverToBoxAdapter(
              child: _Header(data: data, onOpenMenu: onOpenMenu),
            ),
            SliverToBoxAdapter(
              child: _SearchBar(data: data, onOpenSearch: onOpenSearch),
            ),
            if (data.on('ai_assistant'))
              SliverToBoxAdapter(child: _AiBanner(data: data)),
            if (data.vendorTypes.isNotEmpty)
              SliverToBoxAdapter(
                child: HomeCategoriesSection(
                  types: data.vendorTypes,
                  home: data,
                ),
              ),
            ...data.popular.map(
              (section) => SliverToBoxAdapter(
                child: _PopularBlock(section: section, data: data),
              ),
            ),
            const SliverToBoxAdapter(child: SizedBox(height: 96)),
          ],
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.data, required this.onOpenMenu});

  final HomeData data;
  final VoidCallback onOpenMenu;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const BrandHeader(height: 42),
              ],
            ),
          ),
          if (data.on('notifications'))
            ListenableBuilder(
              listenable: NotificationsStore.instance,
              builder: (context, _) => _HeaderIcon(
                icon: Icons.notifications_none_rounded,
                tooltip: 'Notifications',
                onPressed: () => openNotifications(context, data),
                badge: NotificationsStore.instance.unread,
              ),
            ),
          ListenableBuilder(
            listenable: FavoritesStore.instance,
            builder: (context, _) => _HeaderIcon(
              icon: FavoritesStore.instance.count > 0
                  ? Icons.favorite_rounded
                  : Icons.favorite_border_rounded,
              tooltip: 'Favorites',
              onPressed: () => openFavorites(context, data),
              badge: FavoritesStore.instance.count,
            ),
          ),
          _HeaderIcon(
            icon: Icons.menu_rounded,
            tooltip: 'Menu',
            onPressed: onOpenMenu,
          ),
        ],
      ),
    );
  }
}

class _HeaderIcon extends StatelessWidget {
  const _HeaderIcon({
    required this.icon,
    required this.onPressed,
    this.badge = 0,
    this.tooltip,
  });

  final IconData icon;
  final VoidCallback onPressed;
  final int badge;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        IconButton(
          tooltip: tooltip,
          onPressed: onPressed,
          visualDensity: VisualDensity.compact,
          icon: Icon(icon, color: LensColors.cream, size: 26),
        ),
        if (badge > 0)
          Positioned(
            right: 6,
            top: 6,
            child: IgnorePointer(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                decoration: BoxDecoration(
                  color: LensColors.primary,
                  borderRadius: BorderRadius.circular(99),
                ),
                child: Text(
                  badge > 99 ? '99+' : '$badge',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class _SearchBar extends StatelessWidget {
  const _SearchBar({required this.data, required this.onOpenSearch});

  final HomeData data;
  final VoidCallback onOpenSearch;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: onOpenSearch,
              child: Container(
                height: 52,
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFF1C1D22),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFF2A2D34)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.search, color: LensColors.slate),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        data.searchPlaceholder,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: LensColors.slate,
                          fontSize: 14,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          if (data.on('filters')) ...[
            const SizedBox(width: 10),
            Material(
              color: LensColors.primary,
              borderRadius: BorderRadius.circular(14),
              child: InkWell(
                key: const Key('home-filter-button'),
                onTap: () => _openHomeFilters(context, data),
                borderRadius: BorderRadius.circular(14),
                child: const SizedBox(
                  width: 52,
                  height: 52,
                  child: Icon(Icons.tune_rounded, color: Colors.white),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _AiBanner extends StatelessWidget {
  const _AiBanner({required this.data});

  final HomeData data;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final compact = width < 720;
    final scale = compact ? width / 767 : 1.0;

    return Padding(
      padding: EdgeInsets.fromLTRB(width * 0.051, 4, width * 0.051, 18),
      child: Container(
        key: const Key('home-ai-search-banner'),
        padding: EdgeInsets.symmetric(
          horizontal: 20 * scale,
        ),
        decoration: BoxDecoration(
          color: const Color(0xFF0D1112),
          borderRadius: BorderRadius.circular(16 * scale),
          border: Border.all(
            color: const Color(0xFF252A2B),
            width: scale,
          ),
        ),
        child: SizedBox(
          height: 120 * scale,
          child: Stack(
            alignment: Alignment.centerLeft,
            children: [
              Row(
                children: [
                  _AiSparkle(size: 54 * scale),
                  SizedBox(width: 16 * scale),
                  Expanded(
                    child: Transform.translate(
                      offset: Offset(0, 7 * scale),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            data.aiPrompt,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w700,
                              fontSize: 26 * scale,
                              height: 1.15,
                            ),
                          ),
                          SizedBox(height: 12 * scale),
                          Text(
                            data.aiHelper,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: const Color(0xFFE2E2E2),
                              fontSize: 18 * scale,
                              height: 1.15,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              Positioned(
                top: 24 * scale,
                right: 0,
                child: _AiSearchButton(
                  onPressed: () => openAiSearch(context, data),
                  scale: scale,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AiSparkle extends StatelessWidget {
  const _AiSparkle({required this.size});

  final double size;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        children: [
          Icon(
            Icons.auto_awesome,
            color: LensColors.warning,
            size: size * 0.72,
          ),
          Positioned(
            right: 0,
            bottom: 2,
            child: Icon(
              Icons.auto_awesome,
              color: LensColors.warning,
              size: size * 0.38,
            ),
          ),
        ],
      ),
    );
  }
}

class _AiSearchButton extends StatelessWidget {
  const _AiSearchButton({required this.onPressed, required this.scale});

  final VoidCallback onPressed;
  final double scale;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      key: const Key('home-ai-search-button'),
      width: 196 * scale,
      height: 52 * scale,
      child: Material(
        color: LensColors.primary,
        borderRadius: BorderRadius.circular(999 * scale),
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(999 * scale),
          child: Padding(
            padding: EdgeInsets.symmetric(horizontal: 16 * scale),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.auto_awesome, size: 20 * scale, color: Colors.white),
                  SizedBox(width: 8 * scale),
                  Text(
                    'AI Search',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 18 * scale,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  SizedBox(width: 8 * scale),
                  Icon(
                    Icons.chevron_right_rounded,
                    size: 24 * scale,
                    color: Colors.white,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _PopularBlock extends StatelessWidget {
  const _PopularBlock({required this.section, required this.data});

  final PopularSection section;
  final HomeData data;

  @override
  Widget build(BuildContext context) {
    if (section.vendors.isEmpty) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
            child: _SectionHead(
              title: section.title,
              viewAllKey: Key('view-all-${section.slug}'),
              onViewAll: () => _openCategory(
                context,
                data,
                _typeFor(data, section.slug, section.title),
                vendors: section.vendors,
              ),
            ),
          ),
          SizedBox(
            height: 248,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
              itemCount: section.vendors.length,
              separatorBuilder: (_, __) => const SizedBox(width: 14),
              itemBuilder: (context, index) {
                return SizedBox(
                  width: MediaQuery.sizeOf(context).width * 0.88,
                  child: _VendorSpotlight(
                    vendor: section.vendors[index],
                    data: data,
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _VendorSpotlight extends StatelessWidget {
  const _VendorSpotlight({required this.vendor, required this.data});

  final VendorCard vendor;
  final HomeData data;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(24),
      child: SizedBox(
        height: 240,
        child: GestureDetector(
          onTap: () => openVendorProfile(context, vendor, data),
          child: Stack(
            fit: StackFit.expand,
            children: [
              _Cover(
                url: vendor.coverUrl,
                typeSlug: vendor.vendorType,
                seed: vendor.id,
              ),
              const DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    stops: [0.38, 0.72, 1],
                    colors: [
                      Colors.transparent,
                      Color(0x66000000),
                      Color(0xF2000000),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 12, 14),
                child: Column(
                  children: [
                    Row(
                      children: [
                        if (data.on('badges') &&
                            vendor.badges.contains('top-rated'))
                          const _TopRatedBadge(),
                        const Spacer(),
                        FavoriteHeart(vendor: vendor, size: 16, circle: true),
                      ],
                    ),
                    const Spacer(),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        _VendorAvatar(vendor: vendor),
                        const SizedBox(width: 10),
                        Expanded(
                          child: _VendorMeta(vendor: vendor, data: data),
                        ),
                        const SizedBox(width: 8),
                        _ViewProfileButton(
                          onPressed: () =>
                              openVendorProfile(context, vendor, data),
                        ),
                      ],
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
}

class _ViewProfileButton extends StatelessWidget {
  const _ViewProfileButton({required this.onPressed});

  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(999),
        child: Ink(
          decoration: BoxDecoration(
            color: const Color(0xE6000000),
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: LensColors.primary, width: 1.6),
          ),
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 18, vertical: 10),
            child: Text(
              'View Profile',
              style: TextStyle(
                color: LensColors.primary,
                fontSize: 13,
                fontWeight: FontWeight.w700,
                height: 1.1,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _TopRatedBadge extends StatelessWidget {
  const _TopRatedBadge();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xCC141518),
        borderRadius: BorderRadius.circular(20),
      ),
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.workspace_premium, color: Color(0xFFF5C451), size: 15),
          SizedBox(width: 4),
          Text(
            'Top Rated',
            style: TextStyle(
              color: Colors.white,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _VendorAvatar extends StatelessWidget {
  const _VendorAvatar({required this.vendor});

  final VendorCard vendor;

  @override
  Widget build(BuildContext context) {
    final photo = vendor.profilePhotoUrl;

    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 1.5),
        color: LensColors.graphite,
      ),
      clipBehavior: Clip.antiAlias,
      alignment: Alignment.center,
      child: photo == null || photo.isEmpty
          ? Text(
              vendor.initials,
              style: const TextStyle(
                color: LensColors.cream,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            )
          : Image.network(
              photo,
              fit: BoxFit.cover,
              width: 44,
              height: 44,
              errorBuilder: (context, error, stackTrace) => Text(
                vendor.initials,
                style: const TextStyle(
                  color: LensColors.cream,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
    );
  }
}

class _VendorMeta extends StatelessWidget {
  const _VendorMeta({required this.vendor, required this.data});

  final VendorCard vendor;
  final HomeData data;

  @override
  Widget build(BuildContext context) {
    const metaStyle = TextStyle(
      color: Color(0xFFD0CBC3),
      fontSize: 12,
      height: 1.2,
    );
    final location = vendor.location.isEmpty ? vendor.city : vendor.location;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          vendor.publicName,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 16,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 2),
        Row(
          children: [
            Flexible(
              child: Text(
                vendor.vendorTypeName,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: metaStyle,
              ),
            ),
            if (data.on('reviews')) ...[
              const SizedBox(width: 6),
              const Icon(
                Icons.star_rounded,
                color: Color(0xFFF5C451),
                size: 14,
              ),
              const SizedBox(width: 2),
              Text(
                '${vendor.ratingAvg.toStringAsFixed(1)} (${vendor.ratingCount})',
                style: metaStyle,
              ),
            ],
            if (location.isNotEmpty) ...[
              const Text('  ·  ', style: metaStyle),
              Flexible(
                child: Text(
                  location,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: metaStyle,
                ),
              ),
            ],
          ],
        ),
      ],
    );
  }
}

class _Cover extends StatelessWidget {
  const _Cover({required this.url, required this.typeSlug, required this.seed});

  final String? url;
  final String typeSlug;
  final int seed;

  @override
  Widget build(BuildContext context) {
    if (url == null || url!.isEmpty) {
      return Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFF4A1E0C), LensColors.charcoal],
          ),
        ),
      );
    }

    return Image.network(
      url!,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => Image.network(
        VendorPhotos.cover(typeSlug, seed),
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) => Container(
          color: LensColors.graphite,
          alignment: Alignment.center,
          child: const Icon(
            Icons.photo_camera_outlined,
            color: LensColors.primary,
            size: 42,
          ),
        ),
      ),
    );
  }
}

class _SectionHead extends StatelessWidget {
  const _SectionHead({
    required this.title,
    required this.onViewAll,
    this.viewAllKey,
  });

  final String title;
  final VoidCallback onViewAll;
  final Key? viewAllKey;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            key: viewAllKey,
            onTap: onViewAll,
            child: const Text(
              'View all',
              style: TextStyle(
                color: LensColors.primary,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

VendorTypeItem _typeFor(HomeData home, String slug, [String? fallback]) {
  for (final type in home.vendorTypes) {
    if (type.slug == slug) {
      return type;
    }
  }

  return VendorTypeItem(slug: slug, label: fallback ?? slug);
}

Future<void> _openHomeFilters(BuildContext context, HomeData data) async {
  final types = data.vendorTypes;
  final first = types.isEmpty
      ? const VendorTypeItem(slug: 'photographer', label: 'Photographers')
      : types.first;
  final result = await Navigator.of(context).push<CategoryFilters>(
    MaterialPageRoute<CategoryFilters>(
      builder: (_) => FilterPage(
        title: first.label,
        vendorTypeSlug: first.slug,
        catalog: data.filterCatalog,
        types: types,
      ),
    ),
  );
  if (result == null || !context.mounted) {
    return;
  }
  final type = _typeFor(data, result.vendorSlug ?? first.slug, first.label);
  _openCategory(context, data, type, initialFilters: result);
}

void _openCategory(
  BuildContext context,
  HomeData home,
  VendorTypeItem type, {
  List<VendorCard>? vendors,
  CategoryFilters? initialFilters,
}) {
  final items =
      vendors ??
      home.popular
          .where((section) => section.slug == type.slug)
          .expand((section) => section.vendors)
          .toList();

  Navigator.of(context).push(
    MaterialPageRoute<void>(
      builder: (_) => CategoryListPage(
        type: type,
        home: home,
        vendors: items,
        initialFilters: initialFilters,
      ),
    ),
  );
}
