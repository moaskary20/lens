import 'package:flutter/material.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key, required this.data, required this.onOpenMenu, required this.onOpenSearch});

  final HomeData data;
  final VoidCallback onOpenMenu;
  final VoidCallback onOpenSearch;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: CustomScrollView(
        cacheExtent: 1200,
        slivers: [
          SliverToBoxAdapter(child: _Header(data: data, onOpenMenu: onOpenMenu)),
          SliverToBoxAdapter(child: _SearchBar(data: data, onOpenSearch: onOpenSearch)),
          if (data.on('ai_assistant')) SliverToBoxAdapter(child: _AiBanner(data: data)),
          if (data.vendorTypes.isNotEmpty) SliverToBoxAdapter(child: _Categories(types: data.vendorTypes, home: data)),
          ...data.popular.map((section) => SliverToBoxAdapter(child: _PopularBlock(section: section, data: data))),
          const SliverToBoxAdapter(child: SizedBox(height: 96)),
        ],
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
                const Text(
                  'Lens',
                  style: TextStyle(
                    color: LensColors.primary,
                    fontSize: 34,
                    fontWeight: FontWeight.w800,
                    height: 1,
                  ),
                ),
                const SizedBox(height: 4),
                Text(data.tagline, style: const TextStyle(color: LensColors.slate, fontSize: 13)),
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
              icon: FavoritesStore.instance.count > 0 ? Icons.favorite_rounded : Icons.favorite_border_rounded,
              tooltip: 'Favorites',
              onPressed: () => openFavorites(context, data),
              badge: FavoritesStore.instance.count,
            ),
          ),
          _HeaderIcon(
            icon: Icons.menu_rounded,
            onPressed: onOpenMenu,
          ),
        ],
      ),
    );
  }
}

class _HeaderIcon extends StatelessWidget {
  const _HeaderIcon({required this.icon, required this.onPressed, this.badge = 0, this.tooltip});

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
                decoration: BoxDecoration(color: LensColors.primary, borderRadius: BorderRadius.circular(99)),
                child: Text(
                  badge > 99 ? '99+' : '$badge',
                  style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700),
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
                        style: const TextStyle(color: LensColors.slate, fontSize: 14),
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
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(builder: (_) => const PlaceholderPage(title: 'Filters')),
                ),
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
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 18),
      child: Container(
        padding: const EdgeInsets.fromLTRB(16, 16, 14, 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          gradient: const LinearGradient(
            colors: [Color(0xFF5A220F), Color(0xFF2A140E), Color(0xFF1A1B1F)],
          ),
          border: Border.all(color: const Color(0x66FF5A1F)),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.auto_awesome, color: LensColors.warning, size: 18),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          data.aiPrompt,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(data.aiHelper, style: const TextStyle(color: Color(0xFFD8D2C8), fontSize: 12, height: 1.35)),
                ],
              ),
            ),
            const SizedBox(width: 8),
            FilledButton(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => const PlaceholderPage(title: 'AI Search')),
              ),
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.auto_awesome, size: 14),
                  SizedBox(width: 4),
                  Text('AI Search', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12)),
                  Icon(Icons.chevron_right, size: 16),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Categories extends StatelessWidget {
  const _Categories({required this.types, required this.home});

  final List<VendorTypeItem> types;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
      child: Column(
        children: [
          _SectionHead(
            title: 'Categories',
            onViewAll: () => _openCategory(context, home, types.first),
          ),
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: types.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 4,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              mainAxisExtent: 86,
            ),
            itemBuilder: (context, index) {
              final type = types[index];
              return _CategoryTile(type: type, featured: index == 0, home: home);
            },
          ),
        ],
      ),
    );
  }
}

class _CategoryTile extends StatelessWidget {
  const _CategoryTile({required this.type, required this.featured, required this.home});

  final VendorTypeItem type;
  final bool featured;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: featured ? const Color(0xFF24140E) : const Color(0xFF141518),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => _openCategory(context, home, type),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: featured ? const Color(0x55FF5A1F) : const Color(0xFF22242A)),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(_iconFor(type.slug), color: LensColors.primary, size: 24),
              const SizedBox(height: 6),
              Text(
                type.label,
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: LensColors.cream, fontSize: 10, fontWeight: FontWeight.w600, height: 1.15),
              ),
            ],
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
              onViewAll: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: section.title)),
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
                  child: _VendorSpotlight(vendor: section.vendors[index], data: data),
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
            _Cover(url: vendor.coverUrl, typeSlug: vendor.vendorType, seed: vendor.id),
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  stops: [0.38, 0.72, 1],
                  colors: [Colors.transparent, Color(0x660D0D0F), Color(0xF20D0D0F)],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 12, 12, 14),
              child: Column(
                children: [
                  Row(
                    children: [
                      if (data.on('badges') && vendor.badges.contains('top-rated')) const _TopRatedBadge(),
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
                      Expanded(child: _VendorMeta(vendor: vendor, data: data)),
                      const SizedBox(width: 8),
                      _ViewProfileButton(
                        onPressed: () => openVendorProfile(context, vendor, data),
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
            color: const Color(0xE60D0D0F),
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
          Text('Top Rated', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700)),
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
          ? Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontSize: 13, fontWeight: FontWeight.w700))
          : Image.network(
              photo,
              fit: BoxFit.cover,
              width: 44,
              height: 44,
              errorBuilder: (context, error, stackTrace) => Text(
                vendor.initials,
                style: const TextStyle(color: LensColors.cream, fontSize: 13, fontWeight: FontWeight.w700),
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
    const metaStyle = TextStyle(color: Color(0xFFD0CBC3), fontSize: 12, height: 1.2);
    final location = vendor.location.isEmpty ? vendor.city : vendor.location;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          vendor.displayName,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 2),
        Row(
          children: [
            Flexible(
              child: Text(vendor.vendorTypeName, maxLines: 1, overflow: TextOverflow.ellipsis, style: metaStyle),
            ),
            if (data.on('reviews')) ...[
              const SizedBox(width: 6),
              const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 14),
              const SizedBox(width: 2),
              Text(
                '${vendor.ratingAvg.toStringAsFixed(1)} (${vendor.ratingCount})',
                style: metaStyle,
              ),
            ],
            if (location.isNotEmpty) ...[
              const Text('  ·  ', style: metaStyle),
              Flexible(
                child: Text(location, maxLines: 1, overflow: TextOverflow.ellipsis, style: metaStyle),
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
          child: const Icon(Icons.photo_camera_outlined, color: LensColors.primary, size: 42),
        ),
      ),
    );
  }
}

class _SectionHead extends StatelessWidget {
  const _SectionHead({required this.title, required this.onViewAll});

  final String title;
  final VoidCallback onViewAll;

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
              style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: onViewAll,
            child: const Text('View all', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}

IconData _iconFor(String slug) {
  return switch (slug) {
    'photographer' => Icons.photo_camera_outlined,
    'videographer' => Icons.videocam_outlined,
    'reels' => Icons.phone_iphone,
    'model' => Icons.person_outline,
    'studio' => Icons.home_work_outlined,
    'ugc' => Icons.groups_outlined,
    'food_stylist' => Icons.restaurant,
    _ => Icons.category_outlined,
  };
}

void _openCategory(BuildContext context, HomeData home, VendorTypeItem type) {
  final vendors = home.popular
      .where((section) => section.slug == type.slug)
      .expand((section) => section.vendors)
      .toList();

  Navigator.of(context).push(
    MaterialPageRoute<void>(
      builder: (_) => CategoryListPage(type: type, home: home, vendors: vendors),
    ),
  );
}
