import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/filter_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/map_page.dart';
import 'package:lens/features/home/sort_page.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/shell/lens_nav_bar.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class CategoryListPage extends StatefulWidget {
  const CategoryListPage({
    super.key,
    required this.type,
    required this.home,
    this.vendors = const [],
  });

  final VendorTypeItem type;
  final HomeData home;
  final List<VendorCard> vendors;

  @override
  State<CategoryListPage> createState() => _CategoryListPageState();
}

class _CategoryListPageState extends State<CategoryListPage> {
  late List<VendorCard> _allVendors;
  late List<VendorCard> _vendors;
  String _sort = 'recommended';
  int? _total;
  CategoryFilters _filters = const CategoryFilters();

  @override
  void initState() {
    super.initState();
    _allVendors = List<VendorCard>.from(widget.vendors);
    _vendors = List<VendorCard>.from(widget.vendors);
    if (LensConfig.useNetwork) {
      _load();
    }
  }

  Future<void> _load() async {
    try {
      final payload = await ApiClient().getJson(
        '/app/vendors?vendor_type=${Uri.encodeQueryComponent(widget.type.slug)}&limit=40',
      );
      final vendors = (payload['vendors'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => VendorCard.fromJson(Map<String, dynamic>.from(item)))
          .toList();
      if (!mounted || vendors.isEmpty) {
        return;
      }
      setState(() {
        _allVendors = vendors;
        _total = _asCount(payload['total']) ?? vendors.length;
        _vendors = _sorted(_applyFilters(vendors));
      });
      FavoritesStore.instance.remember(vendors);
    } catch (_) {
      // Keep the Home snapshot when the listing API is offline.
    }
  }

  List<VendorCard> _applyFilters(List<VendorCard> input) {
    return input.where((vendor) {
      final types = _filters.services.difference({'All Types', 'All Categories', 'All Expertise'});
      final cuisines = _filters.accents.difference({'All Accents', 'All Cuisines'});
      if (cuisines.isNotEmpty && vendor.filterTags.isNotEmpty) {
        final hit = cuisines.any((item) {
          final needle = item.toLowerCase();
          return vendor.filterTags.any((tag) => tag.contains(needle.replaceAll(' ', '-')) || needle.contains(tag.replaceAll('-', ' ')));
        });
        if (!hit) {
          return false;
        }
      }
      if (types.isNotEmpty) {
        final haystack = [...vendor.tags, vendor.vendorTypeName, ...vendor.filterTags].map((item) => item.toLowerCase()).toList();
        final hit = types.any((service) {
          final needle = service.toLowerCase().replaceAll(' studio', '');
          return haystack.any((item) => item.contains(needle) || needle.contains(item));
        });
        if (!hit) {
          return false;
        }
      }
      final cities = _filters.cities.difference({'All Cities'});
      if (cities.isNotEmpty) {
        final location = '${vendor.city} ${vendor.location}'.toLowerCase();
        if (!cities.any((city) => location.contains(city.toLowerCase()))) {
          return false;
        }
      }
      final content = _filters.contentTypes.difference({'All Types'});
      if (content.isNotEmpty && vendor.filterTags.isNotEmpty) {
        final hit = content.any((item) {
          final needle = item.toLowerCase();
          return vendor.filterTags.any((tag) => tag.contains(needle.replaceAll(' ', '-')) || needle.contains(tag.replaceAll('-', ' ')));
        });
        if (!hit) {
          return false;
        }
      }
      final equipment = _filters.equipment.difference({'All Equipment'});
      if (equipment.isNotEmpty && vendor.filterTags.isNotEmpty) {
        final hit = equipment.any((item) => vendor.filterTags.any((tag) => tag.contains(item.toLowerCase().replaceAll(' ', '-')) || item.toLowerCase().contains(tag.replaceAll('-', ' '))));
        if (!hit) {
          return false;
        }
      }
      if (widget.type.slug == 'model' && vendor.filterTags.isNotEmpty) {
        final genderSlug = switch (_filters.gender) {
          'Women' => 'model-female',
          'Kids' => 'model-kids',
          _ => 'model-male',
        };
        final hasGender = vendor.filterTags.any((tag) => tag == 'model-male' || tag == 'model-female' || tag == 'model-kids');
        if (hasGender && !vendor.filterTags.contains(genderSlug)) {
          return false;
        }
      }
      final price = vendor.startingFrom;
      if (price != null) {
        if (price < _filters.price.start) {
          return false;
        }
        final openEnded = widget.type.slug == 'studio' ? _filters.price.end >= 2000 : _filters.price.end >= 10000;
        if (!openEnded && price > _filters.price.end) {
          return false;
        }
      }
      if (_filters.minRating != null && vendor.ratingAvg < _filters.minRating!) {
        return false;
      }
      if (_filters.verifiedOnly && !vendor.verified) {
        return false;
      }
      return true;
    }).toList();
  }

  Future<void> _openMap() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => MapPage(type: widget.type, home: widget.home, vendors: _vendors),
      ),
    );
  }

  Future<void> _openFilters() async {
    final result = await Navigator.of(context).push<CategoryFilters>(
      MaterialPageRoute(
        builder: (_) => FilterPage(
          title: widget.type.label,
          vendorTypeSlug: widget.type.slug,
          initial: _filters,
          catalog: widget.home.filterCatalog,
        ),
      ),
    );
    if (!mounted || result == null) {
      return;
    }
    setState(() {
      _filters = result;
      _vendors = _sorted(_applyFilters(_allVendors));
      _total = _vendors.length;
    });
  }

  List<VendorCard> _sorted(List<VendorCard> input) => sortVendors(input, _sort);

  Future<void> _openSort() async {
    final result = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => SortPage(initial: _sort)),
    );
    if (!mounted || result == null) {
      return;
    }
    setState(() {
      _sort = result;
      _vendors = _sorted(_applyFilters(_allVendors));
    });
  }

  @override
  Widget build(BuildContext context) {
    final home = widget.home;
    final count = _total ?? _vendors.length;
    final bookingsOn = home.on('bookings');

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: LensNavBar.fab(
        onPressed: () => _open(context, bookingsOn ? 'New booking' : 'Create'),
      ),
      bottomNavigationBar: LensNavBar.bar(
        bookingsOn: bookingsOn,
        index: 0,
        onSelect: (index) {
          if (index == 0) {
            Navigator.of(context).pop();
            return;
          }
          if (bookingsOn && index == 1) {
            _open(context, 'Bookings');
            return;
          }
          if (index == (bookingsOn ? 2 : 1)) {
            _open(context, 'Search');
            return;
          }
          _open(context, 'Profile');
        },
      ),
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 6, 12, 0),
              child: Row(
                children: [
                  const Expanded(
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
                  IconButton(
                    onPressed: () => _open(context, 'Search'),
                    icon: const Icon(Icons.search, color: LensColors.cream),
                  ),
                  if (home.on('notifications'))
                    IconButton(
                      onPressed: () => openNotifications(context, home),
                      icon: const Icon(Icons.notifications_none_rounded, color: LensColors.cream),
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 4, 20, 8),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
                  ),
                  Expanded(
                    child: Text(
                      widget.type.label,
                      style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
              child: Row(
                children: [
                  Expanded(child: _ActionPill(icon: Icons.swap_vert_rounded, label: 'Sort', onTap: _openSort)),
                  const SizedBox(width: 8),
                  Expanded(child: _ActionPill(icon: Icons.tune_rounded, label: 'Filter', onTap: _openFilters)),
                  const SizedBox(width: 8),
                  Expanded(child: _ActionPill(icon: Icons.location_on_outlined, label: 'Map', onTap: _openMap)),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 12, 8),
              child: Row(
                children: [
                  Text(
                    '$count ${widget.type.label}',
                    style: const TextStyle(color: LensColors.slate, fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  const Spacer(),
                  InkWell(
                    onTap: _openSort,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                      child: Row(
                        children: [
                          Text(
                            _sortLabel,
                            style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700),
                          ),
                          const Icon(Icons.expand_more_rounded, color: LensColors.primary, size: 20),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 96),
                itemCount: _vendors.length,
                separatorBuilder: (_, __) => const Divider(color: Color(0xFF22242A), height: 28),
                itemBuilder: (context, index) => _ListingRow(vendor: _vendors[index], home: home),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String get _sortLabel => switch (_sort) {
        'rating' => 'Top rated',
        'reviews' => 'Best review',
        'location' => 'Location',
        'price_asc' => 'Price: low',
        'price_desc' => 'Price: high',
        _ => 'Recommended',
      };

  void _open(BuildContext context, String title) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: title)));
  }
}

class _ActionPill extends StatelessWidget {
  const _ActionPill({required this.icon, required this.label, this.onTap});

  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final body = Container(
      height: 48,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2D34)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: LensColors.cream, size: 18),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
        ],
      ),
    );

    if (onTap == null) {
      return body;
    }

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: body,
      ),
    );
  }
}

class _ListingRow extends StatelessWidget {
  const _ListingRow({required this.vendor, required this.home});

  final VendorCard vendor;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    final location = vendor.location.isEmpty ? vendor.city : vendor.location;

    return InkWell(
      onTap: () => openVendorProfile(context, vendor, home),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(16),
            child: SizedBox(
              width: 86,
              height: 86,
              child: _Thumb(url: vendor.coverUrl ?? vendor.profilePhotoUrl, typeSlug: vendor.vendorType, seed: vendor.id),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        vendor.displayName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                      ),
                    ),
                    if (vendor.verified) ...[
                      const SizedBox(width: 4),
                      const Icon(Icons.verified, color: Color(0xFF3D8B5F), size: 16),
                    ],
                    const Spacer(),
                    FavoriteHeart(vendor: vendor, size: 20, color: LensColors.slate),
                  ],
                ),
                if (home.on('reviews')) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 16),
                      const SizedBox(width: 2),
                      Text(
                        vendor.ratingAvg.toStringAsFixed(1),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13),
                      ),
                      Text(
                        '  (${vendor.ratingCount})',
                        style: const TextStyle(color: LensColors.slate, fontSize: 12),
                      ),
                    ],
                  ),
                ],
                if (vendor.tags.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: vendor.tags.take(3).map((tag) {
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1A1B1F),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFF2A2D34)),
                        ),
                        child: Text(tag, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 11, fontWeight: FontWeight.w600)),
                      );
                    }).toList(),
                  ),
                ],
                const SizedBox(height: 8),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    const Icon(Icons.location_on_outlined, color: LensColors.slate, size: 16),
                    const SizedBox(width: 2),
                    Expanded(
                      child: Text(
                        location,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: LensColors.slate, fontSize: 12),
                      ),
                    ),
                    if (vendor.startingFrom != null)
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Starting from', style: TextStyle(color: LensColors.slate, fontSize: 11)),
                          Text(
                            _money(vendor.startingFrom!),
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14),
                          ),
                        ],
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Thumb extends StatelessWidget {
  const _Thumb({required this.url, required this.typeSlug, required this.seed});

  final String? url;
  final String typeSlug;
  final int seed;

  @override
  Widget build(BuildContext context) {
    if (url == null || url!.isEmpty) {
      return Container(
        color: LensColors.graphite,
        alignment: Alignment.center,
        child: const Icon(Icons.photo_camera_outlined, color: LensColors.primary),
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
          child: const Icon(Icons.photo_camera_outlined, color: LensColors.primary),
        ),
      ),
    );
  }
}

int? _asCount(Object? value) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value);
  }
  return null;
}

String _money(double value) {
  final raw = value.round().toString();
  final buffer = StringBuffer('EGP ');
  for (var i = 0; i < raw.length; i++) {
    if (i > 0 && (raw.length - i) % 3 == 0) {
      buffer.write(',');
    }
    buffer.write(raw[i]);
  }
  return buffer.toString();
}
