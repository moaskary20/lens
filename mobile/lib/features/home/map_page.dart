import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/filter_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/home/sort_page.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/shell/lens_nav_bar.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class MapPage extends StatefulWidget {
  const MapPage({
    super.key,
    required this.type,
    required this.home,
    required this.vendors,
    this.city,
  });

  final VendorTypeItem type;
  final HomeData home;
  final List<VendorCard> vendors;
  final String? city;

  @override
  State<MapPage> createState() => _MapPageState();
}

class _MapPageState extends State<MapPage> {
  late List<VendorCard> _vendors;
  String _sort = 'location';
  String _chip = 'All Types';
  final MapController _map = MapController();
  int? _selectedId;

  @override
  void initState() {
    super.initState();
    _vendors = sortVendors(widget.vendors, _sort);
  }

  @override
  void dispose() {
    _map.dispose();
    super.dispose();
  }

  String get _cityName {
    if (widget.city != null && widget.city!.isNotEmpty) {
      return widget.city!;
    }
    return _vendors.isEmpty ? 'Cairo' : _vendors.first.city;
  }

  LatLng get _center {
    if (_visible.isNotEmpty) {
      final vendor = _visible.first;
      return LatLng(vendor.mapLatitude, vendor.mapLongitude);
    }
    return LatLng(LensCities.latitude(_cityName), LensCities.longitude(_cityName));
  }

  List<VendorCard> get _visible {
    if (_chip == 'All Types') {
      return _vendors;
    }
    final needle = _chip.toLowerCase();
    return _vendors.where((vendor) {
      return [...vendor.tags, vendor.vendorTypeName].any((item) => item.toLowerCase().contains(needle));
    }).toList();
  }

  List<String> get _chips {
    if (widget.type.slug == 'studio') {
      return const ['All Types', 'Photo', 'Video', 'Podcast', 'Event Space', 'Green Screen'];
    }
    final tags = <String>{};
    for (final vendor in widget.vendors) {
      tags.addAll(vendor.tags);
    }
    return ['All Types', ...tags.take(5)];
  }

  String get _sortLabel => switch (_sort) {
        'location' => 'Distance',
        'rating' => 'Top rated',
        'reviews' => 'Best review',
        'price_asc' => 'Price: low',
        'price_desc' => 'Price: high',
        _ => 'Recommended',
      };

  void _fitVendors() {
    var items = _visible;
    final inCity = items
        .where((vendor) => vendor.city.toLowerCase() == _cityName.toLowerCase())
        .toList();
    if (inCity.length >= 2) {
      items = inCity;
    }
    if (items.isEmpty) {
      return;
    }
    if (items.length == 1) {
      _map.move(LatLng(items.first.mapLatitude, items.first.mapLongitude), 13);
      return;
    }
    _map.fitCamera(
      CameraFit.coordinates(
        coordinates: [for (final vendor in items) LatLng(vendor.mapLatitude, vendor.mapLongitude)],
        padding: const EdgeInsets.fromLTRB(36, 28, 64, 28),
        maxZoom: 13.4,
      ),
    );
  }

  Future<void> _openSort() async {
    final result = await Navigator.of(context).push<String>(
      MaterialPageRoute(builder: (_) => SortPage(initial: _sort)),
    );
    if (!mounted || result == null) {
      return;
    }
    setState(() {
      _sort = result;
      _vendors = sortVendors(widget.vendors, _sort);
    });
  }

  void _zoom(double delta) {
    _map.move(_map.camera.center, (_map.camera.zoom + delta).clamp(4, 18));
  }

  @override
  Widget build(BuildContext context) {
    final bookingsOn = widget.home.on('bookings');
    final items = _visible;

    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: LensNavBar.fab(
        onPressed: () => _open(context, bookingsOn ? 'New booking' : 'Create'),
      ),
      bottomNavigationBar: LensNavBar.bar(
        bookingsOn: bookingsOn,
        index: bookingsOn ? 2 : 1,
        onSelect: (index) {
          if (index == 0) {
            Navigator.of(context).popUntil((route) => route.isFirst);
            return;
          }
          if (bookingsOn && index == 1) {
            _open(context, 'Bookings');
            return;
          }
          if (index == (bookingsOn ? 2 : 1)) {
            return;
          }
          _open(context, 'Profile');
        },
      ),
      body: SafeArea(
        child: Column(
          children: [
            _brandBar(),
            _titleRow(),
            _modePills(),
            _searchRow(),
            _chipRow(),
            Expanded(flex: 11, child: _mapStack(items)),
            _listHeader(items.length),
            Expanded(flex: 10, child: _results(items)),
          ],
        ),
      ),
    );
  }

  Widget _brandBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 6, 12, 0),
      child: Row(
        children: [
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Lens.',
                  style: TextStyle(color: LensColors.primary, fontSize: 28, fontWeight: FontWeight.w800, height: 1),
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
          if (widget.home.on('notifications'))
            IconButton(
              onPressed: () => openNotifications(context, widget.home),
              icon: const Icon(Icons.notifications_none_rounded, color: LensColors.cream),
            ),
        ],
      ),
    );
  }

  Widget _titleRow() {
    return Padding(
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
    );
  }

  Widget _modePills() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 10),
      child: Row(
        children: [
          Expanded(
            child: _pill(icon: Icons.view_agenda_outlined, label: 'List', onTap: () => Navigator.of(context).pop()),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _pill(
              icon: Icons.tune_rounded,
              label: 'Filter',
              onTap: () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => FilterPage(
                    title: widget.type.label,
                    vendorTypeSlug: widget.type.slug,
                    catalog: widget.home.filterCatalog,
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _pill(icon: Icons.location_on_outlined, label: 'Map', selected: true),
          ),
        ],
      ),
    );
  }

  Widget _searchRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 10),
      child: Row(
        children: [
          Expanded(
            child: Container(
              height: 46,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              decoration: BoxDecoration(
                color: const Color(0xFF121316),
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: const Color(0xFF2A2D34)),
              ),
              child: const Row(
                children: [
                  Icon(Icons.search, color: LensColors.slate, size: 20),
                  SizedBox(width: 8),
                  Text('Search in this area', style: TextStyle(color: LensColors.slate, fontSize: 14)),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          _roundIcon(Icons.gps_fixed_rounded, onTap: _fitVendors),
        ],
      ),
    );
  }

  Widget _chipRow() {
    return SizedBox(
      height: 40,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
        itemCount: _chips.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final chip = _chips[index];
          final selected = chip == _chip;
          return GestureDetector(
            onTap: () {
              setState(() => _chip = chip);
              WidgetsBinding.instance.addPostFrameCallback((_) => _fitVendors());
            },
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: selected ? Colors.white : const Color(0xFF121316),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: selected ? Colors.white : const Color(0xFF2A2D34)),
              ),
              child: Text(
                chip,
                style: TextStyle(
                  color: selected ? Colors.black : Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _mapStack(List<VendorCard> items) {
    return Stack(
      children: [
        const Positioned.fill(child: ColoredBox(color: Color(0xFF1B2028))),
        Positioned.fill(child: _mapCanvas(items)),
        Positioned(
          right: 12,
          bottom: 16,
          child: Column(
            children: [
              Material(
                color: const Color(0xF2FFFFFF),
                borderRadius: BorderRadius.circular(8),
                child: Column(
                  children: [
                    _zoomButton(Icons.add, () => _zoom(1)),
                    Container(width: 22, height: 1, color: const Color(0x33000000)),
                    _zoomButton(Icons.remove, () => _zoom(-1)),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              _roundIcon(Icons.near_me_outlined, onTap: _fitVendors),
            ],
          ),
        ),
      ],
    );
  }

  Widget _mapCanvas(List<VendorCard> items) {
    return FlutterMap(
      mapController: _map,
      options: MapOptions(
        initialCenter: _center,
        initialZoom: 11.6,
        backgroundColor: const Color(0xFF1B2028),
        interactionOptions: const InteractionOptions(flags: InteractiveFlag.all & ~InteractiveFlag.rotate),
        onTap: (tap, point) => setState(() => _selectedId = null),
        onMapReady: _fitVendors,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
          userAgentPackageName: 'app.lens.lens',
        ),
        MarkerLayer(
          markers: [
            for (final area in _areasFor(_cityName))
              Marker(
                point: LatLng(area.lat, area.lng),
                width: 92,
                height: 36,
                child: _AreaLabel(english: area.english, arabic: area.arabic),
              ),
          ],
        ),
        MarkerLayer(
          markers: [
            for (final vendor in items)
              Marker(
                point: LatLng(vendor.mapLatitude, vendor.mapLongitude),
                width: 108,
                height: 118,
                alignment: Alignment.bottomCenter,
                child: _MapPin(
                  vendor: vendor,
                  selected: vendor.id == _selectedId,
                  onTap: () {
                    setState(() => _selectedId = vendor.id);
                  },
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _listHeader(int count) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 12, 6),
      child: Row(
        children: [
          Text(
            '$count ${widget.type.label}',
            style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
          ),
          const Spacer(),
          InkWell(
            onTap: _openSort,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
              child: Row(
                children: [
                  Text(
                    'Sort by: $_sortLabel',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                  ),
                  const Icon(Icons.expand_more_rounded, color: Colors.white, size: 18),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _results(List<VendorCard> items) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 96),
      itemCount: items.length,
      separatorBuilder: (_, __) => const Divider(color: Color(0xFF22242A), height: 22),
      itemBuilder: (context, index) {
        final vendor = items[index];
        return _ResultRow(
          vendor: vendor,
          home: widget.home,
          selected: vendor.id == _selectedId,
          hourly: widget.type.slug == 'studio',
        );
      },
    );
  }

  static Widget _pill({required IconData icon, required String label, VoidCallback? onTap, bool selected = false}) {
    final color = selected ? LensColors.primary : LensColors.cream;
    final body = Container(
      height: 48,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: selected ? const Color(0xFF1A120C) : const Color(0xFF141518),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2D34), width: selected ? 1.6 : 1),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: selected ? LensColors.primary : Colors.white, fontWeight: FontWeight.w700)),
        ],
      ),
    );
    if (onTap == null) {
      return body;
    }
    return Material(
      color: Colors.transparent,
      child: InkWell(onTap: onTap, borderRadius: BorderRadius.circular(18), child: body),
    );
  }

  static Widget _roundIcon(IconData icon, {VoidCallback? onTap}) {
    return Material(
      color: const Color(0xFF121316),
      shape: const CircleBorder(),
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: SizedBox(
          width: 42,
          height: 42,
          child: Icon(icon, color: Colors.white, size: 18),
        ),
      ),
    );
  }

  static Widget _zoomButton(IconData icon, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: SizedBox(
        width: 36,
        height: 36,
        child: Icon(icon, color: const Color(0xFF1A1B1F), size: 18),
      ),
    );
  }

  void _open(BuildContext context, String title) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: title)));
  }
}

class _MapPin extends StatelessWidget {
  const _MapPin({required this.vendor, required this.onTap, this.selected = false});

  final VendorCard vendor;
  final VoidCallback onTap;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final photo = vendor.profilePhotoUrl ?? vendor.coverUrl;

    return GestureDetector(
      onTap: onTap,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            vendor.displayName,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 10,
              fontWeight: FontWeight.w800,
              shadows: [Shadow(color: Colors.black, blurRadius: 6)],
            ),
          ),
          Container(
            margin: const EdgeInsets.only(top: 2, bottom: 4),
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: const Color(0xE6121318),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 11),
                const SizedBox(width: 2),
                Text(
                  vendor.ratingAvg.toStringAsFixed(1),
                  style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700),
                ),
              ],
            ),
          ),
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: selected ? Colors.white : LensColors.primary, width: 2.6),
              boxShadow: const [BoxShadow(color: Color(0x66000000), blurRadius: 8)],
            ),
            clipBehavior: Clip.antiAlias,
            child: _PinPhoto(url: photo, initials: vendor.initials),
          ),
          const Icon(Icons.location_on, color: LensColors.primary, size: 18),
        ],
      ),
    );
  }
}

class _PinPhoto extends StatelessWidget {
  const _PinPhoto({required this.url, required this.initials});

  final String? url;
  final String initials;

  @override
  Widget build(BuildContext context) {
    if (!LensConfig.useNetwork || url == null || url!.isEmpty) {
      return ColoredBox(
        color: LensColors.graphite,
        child: Center(child: Text(initials, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12))),
      );
    }
    return Image.network(
      url!,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => ColoredBox(
        color: LensColors.graphite,
        child: Center(child: Text(initials, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12))),
      ),
    );
  }
}

class _AreaLabel extends StatelessWidget {
  const _AreaLabel({required this.english, required this.arabic});

  final String english;
  final String arabic;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          english,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 11,
            fontWeight: FontWeight.w700,
            shadows: [Shadow(color: Colors.black, blurRadius: 8)],
          ),
        ),
        Text(
          arabic,
          style: const TextStyle(
            color: Color(0xE6FFFFFF),
            fontSize: 10,
            shadows: [Shadow(color: Colors.black, blurRadius: 8)],
          ),
        ),
      ],
    );
  }
}

class _ResultRow extends StatelessWidget {
  const _ResultRow({
    required this.vendor,
    required this.home,
    required this.hourly,
    this.selected = false,
  });

  final VendorCard vendor;
  final HomeData home;
  final bool hourly;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final location = vendor.location.isEmpty ? vendor.city : vendor.location;
    final photo = vendor.coverUrl ?? vendor.profilePhotoUrl;

    return InkWell(
      onTap: () => openVendorProfile(context, vendor, home),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 4),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? LensColors.primary : Colors.transparent),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                width: 88,
                height: 68,
                child: _PinPhoto(url: photo, initials: vendor.initials),
              ),
            ),
            const SizedBox(width: 10),
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
                          style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800),
                        ),
                      ),
                      if (vendor.verified) ...[
                        const SizedBox(width: 4),
                        const Icon(Icons.verified, color: Color(0xFF3B9BFF), size: 15),
                      ],
                      const Spacer(),
                      FavoriteHeart(vendor: vendor, size: 18, color: LensColors.slate),
                    ],
                  ),
                  if (home.on('reviews')) ...[
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 14),
                        const SizedBox(width: 2),
                        Text(
                          vendor.ratingAvg.toStringAsFixed(1),
                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
                        ),
                        Text(
                          '  (${vendor.ratingCount})',
                          style: const TextStyle(color: LensColors.slate, fontSize: 12),
                        ),
                      ],
                    ),
                  ],
                  if (vendor.tags.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: vendor.tags.take(3).map((tag) {
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1A1B1F),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: const Color(0xFF2A2D34)),
                          ),
                          child: Text(tag, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 10, fontWeight: FontWeight.w600)),
                        );
                      }).toList(),
                    ),
                  ],
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      const Icon(Icons.location_on_outlined, color: LensColors.slate, size: 14),
                      const SizedBox(width: 2),
                      Expanded(
                        child: Text(
                          location,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: LensColors.slate, fontSize: 11),
                        ),
                      ),
                      if (vendor.startingFrom != null)
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('starting from', style: TextStyle(color: LensColors.slate, fontSize: 10)),
                            Text(
                              _money(vendor.startingFrom!, hourly: hourly),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13),
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
      ),
    );
  }
}

class _Area {
  const _Area(this.lat, this.lng, this.english, this.arabic);

  final double lat;
  final double lng;
  final String english;
  final String arabic;
}

List<_Area> _areasFor(String city) {
  if (city.toLowerCase() == 'alexandria') {
    return const [
      _Area(31.2001, 29.9187, 'Alexandria', 'الإسكندرية'),
      _Area(31.235, 29.948, 'Stanley', 'ستانلي'),
      _Area(31.217, 29.944, 'Smouha', 'سموحة'),
      _Area(31.245, 29.966, 'Gleem', 'جليم'),
    ];
  }
  return const [
    _Area(30.062, 31.219, 'Zamalek', 'الزمالك'),
    _Area(30.0444, 31.2357, 'Cairo', 'القاهرة'),
    _Area(30.091, 31.324, 'Heliopolis', 'مصر الجديدة'),
    _Area(30.056, 31.330, 'Nasr City', 'مدينة نصر'),
    _Area(29.960, 31.257, 'Maadi', 'المعادي'),
    _Area(29.972, 30.943, '6th of October', '٦ أكتوبر'),
  ];
}

String _money(double value, {required bool hourly}) {
  final raw = value.round().toString();
  final buffer = StringBuffer('EGP ');
  for (var i = 0; i < raw.length; i++) {
    if (i > 0 && (raw.length - i) % 3 == 0) {
      buffer.write(',');
    }
    buffer.write(raw[i]);
  }
  if (hourly) {
    buffer.write(' / hour');
  }
  return buffer.toString();
}
