import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/category_list_page.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/map_page.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class SearchPage extends StatefulWidget {
  const SearchPage({super.key, required this.data, this.autofocus = false});

  final HomeData data;
  final bool autofocus;

  @override
  State<SearchPage> createState() => _SearchPageState();
}

class _SearchPageState extends State<SearchPage> {
  static const _cities = ['Alexandria', 'Cairo', 'Giza'];
  static const _chipOrder = ['photographer', 'videographer', 'reels', 'studio', 'model', 'ugc', 'food_stylist'];

  final _query = TextEditingController();
  final _focus = FocusNode();
  String _city = 'Cairo';
  String? _typeSlug;

  @override
  void initState() {
    super.initState();
    _typeSlug = _types.isEmpty ? null : _types.first.slug;
    if (widget.autofocus) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) _focus.requestFocus();
      });
    }
  }

  @override
  void didUpdateWidget(covariant SearchPage oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.autofocus && !oldWidget.autofocus) {
      _focus.requestFocus();
    }
  }

  @override
  void dispose() {
    _query.dispose();
    _focus.dispose();
    super.dispose();
  }

  List<VendorTypeItem> get _types {
    final types = [...widget.data.vendorTypes];
    types.sort((a, b) {
      final ai = _chipOrder.indexOf(a.slug);
      final bi = _chipOrder.indexOf(b.slug);
      return (ai < 0 ? 99 : ai).compareTo(bi < 0 ? 99 : bi);
    });
    return types;
  }

  List<VendorCard> get _allVendors {
    final seen = <int>{};
    final items = <VendorCard>[];
    for (final section in widget.data.popular) {
      for (final vendor in section.vendors) {
        if (seen.add(vendor.id)) {
          items.add(vendor);
        }
      }
    }
    return items;
  }

  List<VendorCard> _filtered({String? type}) {
    final needle = _query.text.trim().toLowerCase();
    return _allVendors.where((vendor) {
      if (type != null && type.isNotEmpty && vendor.vendorType != type) {
        return false;
      }
      if (needle.isEmpty) {
        return true;
      }
      final haystack = [
        vendor.displayName,
        vendor.vendorTypeName,
        vendor.city,
        vendor.location,
        ...vendor.tags,
      ].join(' ').toLowerCase();
      return haystack.contains(needle);
    }).toList();
  }

  VendorTypeItem get _activeType {
    for (final type in _types) {
      if (type.slug == _typeSlug) {
        return type;
      }
    }
    return _types.isNotEmpty ? _types.first : const VendorTypeItem(slug: 'photographer', label: 'Photographers');
  }

  VendorTypeItem get _studioType {
    for (final type in _types) {
      if (type.slug == 'studio') {
        return type;
      }
    }
    return _activeType;
  }

  @override
  Widget build(BuildContext context) {
    final recommended = _filtered(type: _typeSlug);
    final topRated = [..._filtered()]..sort((a, b) => b.ratingAvg.compareTo(a.ratingAvg));
    final studios = _filtered(type: 'studio');

    return SafeArea(
      child: CustomScrollView(
        cacheExtent: 800,
        slivers: [
          SliverToBoxAdapter(child: _header(context)),
          SliverToBoxAdapter(child: _searchField()),
          if (_types.isNotEmpty) SliverToBoxAdapter(child: _chips()),
          SliverToBoxAdapter(child: _exploreCard(context, recommended.isEmpty ? _allVendors : recommended)),
          SliverToBoxAdapter(
            child: _creatorRow(
              title: 'Recommended for you',
              vendors: recommended.isEmpty ? _filtered() : recommended,
              type: _activeType,
            ),
          ),
          SliverToBoxAdapter(
            child: _creatorRow(
              title: 'Top Rated Creators',
              vendors: topRated,
              type: _activeType,
            ),
          ),
          SliverToBoxAdapter(
            child: _creatorRow(
              title: 'Studios near you',
              vendors: studios.isEmpty ? _filtered() : studios,
              type: _studioType,
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 96)),
        ],
      ),
    );
  }

  Widget _header(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 12, 4),
      child: Row(
        children: [
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Lens.',
                  style: TextStyle(color: LensColors.primary, fontSize: 32, fontWeight: FontWeight.w800, height: 1),
                ),
                SizedBox(height: 4),
                Text(
                  'FIND. BOOK. CREATE.',
                  style: TextStyle(color: LensColors.primary, fontSize: 9, letterSpacing: 1.5, fontWeight: FontWeight.w800),
                ),
              ],
            ),
          ),
          PopupMenuButton<String>(
            initialValue: _city,
            color: const Color(0xFF161518),
            onSelected: (value) => setState(() => _city = value),
            itemBuilder: (context) => [
              for (final city in _cities) PopupMenuItem(value: city, child: Text(city)),
            ],
            child: Row(
              children: [
                const Icon(Icons.location_on, color: LensColors.primary, size: 16),
                const SizedBox(width: 4),
                Text(_city, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
                const Icon(Icons.keyboard_arrow_down_rounded, color: Colors.white, size: 18),
              ],
            ),
          ),
          if (widget.data.on('notifications'))
            IconButton(
              onPressed: () => openNotifications(context, widget.data),
              icon: const Icon(Icons.notifications_none_rounded, color: LensColors.cream),
            ),
        ],
      ),
    );
  }

  Widget _searchField() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 20, 12),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 50,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: const Color(0xFF161518),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFF2A2D34)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.search, color: LensColors.slate, size: 20),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextField(
                      controller: _query,
                      focusNode: _focus,
                      onChanged: (_) => setState(() {}),
                      style: const TextStyle(color: Colors.white, fontSize: 14),
                      cursorColor: LensColors.primary,
                      decoration: const InputDecoration(
                        hintText: 'What are you looking to create?',
                        hintStyle: TextStyle(color: Color(0xFF7A7D84), fontSize: 14),
                        border: InputBorder.none,
                        isCollapsed: true,
                      ),
                    ),
                  ),
                  const Icon(Icons.mic_none_rounded, color: Color(0xFF9A9AA0), size: 20),
                ],
              ),
            ),
          ),
          if (widget.data.on('ai_assistant')) ...[
            const SizedBox(width: 8),
            Material(
              color: LensColors.primary,
              borderRadius: BorderRadius.circular(14),
              child: InkWell(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(builder: (_) => const PlaceholderPage(title: 'AI Search')),
                ),
                borderRadius: BorderRadius.circular(14),
                child: const SizedBox(
                  width: 50,
                  height: 50,
                  child: Icon(Icons.auto_awesome, color: Colors.white, size: 22),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _chips() {
    return SizedBox(
      height: 86,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
        itemCount: _types.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final type = _types[index];
          final selected = type.slug == _typeSlug;
          return GestureDetector(
            onTap: () => setState(() => _typeSlug = type.slug),
            child: Container(
              width: 78,
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFF141210),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E), width: selected ? 1.4 : 1),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(_iconFor(type.slug), color: LensColors.primary, size: 22),
                  const SizedBox(height: 6),
                  Text(
                    type.slug == 'ugc' ? 'UGC' : type.label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _exploreCard(BuildContext context, List<VendorCard> vendors) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: SizedBox(
          height: 168,
          child: Stack(
            fit: StackFit.expand,
            children: [
              const ColoredBox(color: Color(0xFF151C24)),
              const _ExploreMapPhoto(),
              const CustomPaint(painter: _ExploreMapPainter()),
              const DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                    colors: [Color(0xF214100C), Color(0xCC14100C), Color(0x6614100C), Color(0x0014100C)],
                    stops: [0, 0.28, 0.46, 0.68],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Explore near you', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 4),
                    Text(
                      'Discover amazing creatives in $_city.',
                      style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 12.5),
                    ),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => MapPage(type: _activeType, home: widget.data, vendors: vendors, city: _city),
                        ),
                      ),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(99),
                          border: Border.all(color: LensColors.primary),
                          color: const Color(0xCC1A120C),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text('View Map', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
                            SizedBox(width: 4),
                            Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 16),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const Positioned(
                right: 14,
                top: 14,
                child: _CompassButton(),
              ),
              const _ExplorePin(
                left: 0.46,
                top: 0.34,
                url: 'https://images.unsplash.com/photo-1587595431973-160d0d94add1?auto=format&fit=crop&w=240&h=240&q=80',
              ),
              const _ExplorePin(
                left: 0.64,
                top: 0.10,
                url: 'https://images.unsplash.com/photo-1533929736458-a45524c0bc6d?auto=format&fit=crop&w=240&h=240&q=80',
              ),
              const _ExplorePin(
                left: 0.82,
                top: 0.38,
                url: 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&w=240&h=240&q=80',
              ),
              Positioned(
                right: 86,
                bottom: 46,
                child: Text(_city, style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 12, fontWeight: FontWeight.w700, shadows: [Shadow(color: Colors.black, blurRadius: 8)])),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _creatorRow({required String title, required List<VendorCard> vendors, required VendorTypeItem type}) {
    if (vendors.isEmpty) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
            child: Row(
              children: [
                Expanded(
                  child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                ),
                GestureDetector(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => CategoryListPage(type: type, home: widget.data, vendors: vendors),
                    ),
                  ),
                  child: const Text('View all', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                ),
              ],
            ),
          ),
          SizedBox(
            height: 248,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
              itemCount: vendors.length,
              separatorBuilder: (_, __) => const SizedBox(width: 12),
              itemBuilder: (context, index) {
                return _SearchVendorCard(vendor: vendors[index], home: widget.data);
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _SearchVendorCard extends StatelessWidget {
  const _SearchVendorCard({required this.vendor, required this.home});

  final VendorCard vendor;
  final HomeData home;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => openVendorProfile(context, vendor, home),
      child: Container(
        width: 168,
        decoration: BoxDecoration(
          color: const Color(0xFF141210),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFF2A2A2E)),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              height: 118,
              width: double.infinity,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  _Cover(vendor: vendor),
                  Positioned(
                    right: 8,
                    top: 8,
                    child: FavoriteHeart(vendor: vendor, size: 15, circle: true),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          vendor.displayName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13.5),
                        ),
                      ),
                      if (vendor.verified || vendor.badges.contains('verified'))
                        const Icon(Icons.verified, color: Color(0xFF3B9BFF), size: 14),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(vendor.vendorTypeName, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5)),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 13),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          '${vendor.ratingAvg.toStringAsFixed(1)} (${vendor.ratingCount})  ·  ${vendor.city.isEmpty ? vendor.location : vendor.city}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 11),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(_egp(vendor.startingFrom), style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800, fontSize: 12.5)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Cover extends StatelessWidget {
  const _Cover({required this.vendor});

  final VendorCard vendor;

  @override
  Widget build(BuildContext context) {
    final url = vendor.coverUrl ?? vendor.profilePhotoUrl;
    if (!LensConfig.useNetwork || url == null || url.isEmpty) {
      return const ColoredBox(color: LensColors.graphite);
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => Image.network(
        VendorPhotos.cover(vendor.vendorType, vendor.id),
        fit: BoxFit.cover,
        errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite),
      ),
    );
  }
}

class _ExploreMapPhoto extends StatelessWidget {
  const _ExploreMapPhoto();

  static const _url = 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?auto=format&fit=crop&w=1600&h=800&q=80';

  @override
  Widget build(BuildContext context) {
    if (!LensConfig.useNetwork) {
      return const SizedBox.shrink();
    }
    return ColorFiltered(
      colorFilter: const ColorFilter.mode(Color(0xAA1B2734), BlendMode.multiply),
      child: Image.network(
        _url,
        fit: BoxFit.cover,
        alignment: const Alignment(0.45, 0),
        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
      ),
    );
  }
}

class _CompassButton extends StatelessWidget {
  const _CompassButton();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 34,
      height: 34,
      decoration: BoxDecoration(
        color: const Color(0xCC1A120C),
        shape: BoxShape.circle,
        border: Border.all(color: const Color(0x44FF5A1F)),
      ),
      child: const Icon(Icons.near_me_rounded, color: LensColors.primary, size: 18),
    );
  }
}

class _ExplorePin extends StatelessWidget {
  const _ExplorePin({required this.left, required this.top, required this.url});

  final double left;
  final double top;
  final String url;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment(-1 + left * 2, -1 + top * 2),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              boxShadow: [
                BoxShadow(color: LensColors.primary.withValues(alpha: 0.55), blurRadius: 10, spreadRadius: 0.5),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: _ExplorePinPhoto(url: url),
            ),
          ),
          Container(
            width: 8,
            height: 8,
            margin: const EdgeInsets.only(top: 4),
            decoration: BoxDecoration(
              color: LensColors.primary,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(color: LensColors.primary.withValues(alpha: 0.7), blurRadius: 8),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ExplorePinPhoto extends StatelessWidget {
  const _ExplorePinPhoto({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) {
    if (!LensConfig.useNetwork) {
      return const ColoredBox(color: Color(0xFF2A2D34));
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF2A2D34)),
    );
  }
}

class _ExploreMapPainter extends CustomPainter {
  const _ExploreMapPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final coast = Path()
      ..moveTo(size.width * 0.40, 0)
      ..quadraticBezierTo(size.width * 0.62, size.height * 0.18, size.width * 0.70, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height * 0.42)
      ..quadraticBezierTo(size.width * 0.82, size.height * 0.22, size.width * 0.58, size.height * 0.12)
      ..close();
    canvas.drawPath(coast, Paint()..color = const Color(0x33222C38));

    final route = Path()
      ..moveTo(size.width * 0.40, size.height * 0.82)
      ..cubicTo(size.width * 0.48, size.height * 0.62, size.width * 0.44, size.height * 0.50, size.width * 0.52, size.height * 0.56)
      ..cubicTo(size.width * 0.62, size.height * 0.64, size.width * 0.66, size.height * 0.22, size.width * 0.72, size.height * 0.30)
      ..cubicTo(size.width * 0.80, size.height * 0.40, size.width * 0.84, size.height * 0.62, size.width * 0.90, size.height * 0.58);

    canvas.drawPath(
      route,
      Paint()
        ..color = const Color(0x66FF5A1F)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 10
        ..strokeCap = StrokeCap.round
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 8),
    );
    canvas.drawPath(
      route,
      Paint()
        ..color = const Color(0xAAFF5A1F)
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.4
        ..strokeCap = StrokeCap.round,
    );

    for (final metric in route.computeMetrics()) {
      for (var d = 0.0; d < metric.length; d += 6.5) {
        final tangent = metric.getTangentForOffset(d);
        if (tangent == null) {
          continue;
        }
        canvas.drawCircle(tangent.position, 1.5, Paint()..color = const Color(0xFFFF8A4C));
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

IconData _iconFor(String slug) {
  return switch (slug) {
    'photographer' => Icons.photo_camera_outlined,
    'videographer' => Icons.videocam_outlined,
    'reels' => Icons.movie_creation_outlined,
    'model' => Icons.person_outline,
    'studio' => Icons.apartment_outlined,
    'ugc' => Icons.groups_outlined,
    'food_stylist' => Icons.restaurant,
    _ => Icons.category_outlined,
  };
}

String _egp(double? value) {
  final amount = (value ?? 0).round().toString();
  final withCommas = amount.replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (match) => ',');
  return 'From EGP $withCommas';
}
