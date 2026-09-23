import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/vendor_profile_page.dart';

class FavoritesPage extends StatefulWidget {
  const FavoritesPage({super.key, required this.home});

  final HomeData home;

  @override
  State<FavoritesPage> createState() => _FavoritesPageState();
}

class _FavoritesPageState extends State<FavoritesPage> {
  @override
  void initState() {
    super.initState();
    FavoritesStore.instance.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 6, 20, 8),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
                  ),
                  const Expanded(
                    child: Text('Favorites', style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800)),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListenableBuilder(
                listenable: FavoritesStore.instance,
                builder: (context, _) {
                  final vendors = FavoritesStore.instance.vendors;
                  if (vendors.isEmpty) {
                    return const Center(
                      child: Padding(
                        padding: EdgeInsets.symmetric(horizontal: 36),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.favorite_border_rounded, color: LensColors.primary, size: 42),
                            SizedBox(height: 14),
                            Text('No saved creators yet', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                            SizedBox(height: 8),
                            Text(
                              'Tap the heart on a photographer, studio, or creator to keep them here.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: LensColors.slate, height: 1.4),
                            ),
                          ],
                        ),
                      ),
                    );
                  }
                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
                    itemCount: vendors.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      final vendor = vendors[index];
                      return _FavoriteRow(vendor: vendor, home: widget.home);
                    },
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

class _FavoriteRow extends StatelessWidget {
  const _FavoriteRow({required this.vendor, required this.home});

  final VendorCard vendor;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    final photo = vendor.coverUrl ?? vendor.profilePhotoUrl;
    return Material(
      color: const Color(0xFF141518),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: () => openVendorProfile(context, vendor, home),
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: SizedBox(
                  width: 72,
                  height: 72,
                  child: photo != null && LensConfig.useNetwork
                      ? Image.network(photo, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _initials())
                      : _initials(),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(vendor.displayName, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15)),
                    const SizedBox(height: 3),
                    Text(vendor.vendorTypeName, style: const TextStyle(color: LensColors.slate, fontSize: 12)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 14),
                        const SizedBox(width: 2),
                        Text(vendor.ratingAvg.toStringAsFixed(1), style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12)),
                        const SizedBox(width: 8),
                        const Icon(Icons.location_on_outlined, color: LensColors.slate, size: 13),
                        Expanded(
                          child: Text(
                            vendor.location.isEmpty ? vendor.city : vendor.location,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: LensColors.slate, fontSize: 11),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              FavoriteHeart(vendor: vendor, color: LensColors.slate),
            ],
          ),
        ),
      ),
    );
  }

  Widget _initials() {
    return ColoredBox(
      color: LensColors.graphite,
      child: Center(child: Text(vendor.initials, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700))),
    );
  }
}
