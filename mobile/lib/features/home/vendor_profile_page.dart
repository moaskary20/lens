import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/contact_launch.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_date_page.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/vendor_chat_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

void openVendorProfile(BuildContext context, VendorCard vendor, HomeData home, {bool booked = false}) {
  Navigator.of(context).push(
    MaterialPageRoute<void>(builder: (_) => VendorProfilePage(vendor: vendor, home: home, booked: booked)),
  );
}

class VendorProfilePage extends StatefulWidget {
  const VendorProfilePage({super.key, required this.vendor, required this.home, this.booked = false});

  final VendorCard vendor;
  final HomeData home;
  final bool booked;

  @override
  State<VendorProfilePage> createState() => _VendorProfilePageState();
}

class _VendorProfilePageState extends State<VendorProfilePage> {
  static const _tabs = ['About', 'Specialties', 'Equipment', 'Portfolio', 'Packages', 'Reviews'];

  late String _tab;
  late String _filter;
  late List<String> _filters;
  late List<_Shot> _shots;
  late String _bio;
  late String _profession;
  late String _tagline;
  late String _response;
  late int _projects;
  late int _assisted;
  late String _years;
  late int _satisfaction;
  late List<String> _equipment;
  late List<String> _specialties;
  late List<_Package> _packages;
  String _contactPhone = '';
  String _whatsapp = '';

  VendorCard get vendor => widget.vendor;
  bool get booked => widget.booked;

  String get _phone {
    if (_contactPhone.isNotEmpty) {
      return _contactPhone.replaceAll(RegExp(r'\D'), '');
    }
    return '010${vendor.id.toString().padLeft(8, '0')}';
  }

  String get _whatsappNumber {
    if (_whatsapp.isNotEmpty) {
      return _whatsapp.replaceAll(RegExp(r'\D'), '');
    }
    return _phone;
  }

  @override
  void initState() {
    super.initState();
    _tab = 'Portfolio';
    _hydrateFromCard();
    if (LensConfig.useNetwork) {
      _load();
    }
  }

  void _hydrateFromCard() {
    final type = vendor.vendorType;
    _filters = _filtersFor(type);
    _filter = 'All';
    _shots = _shotsFor(vendor);
    _bio = '${vendor.displayName} is a ${vendor.vendorTypeName.toLowerCase()} based in ${vendor.city.isEmpty ? 'Cairo' : vendor.city}, creating booked work across ${vendor.tags.isEmpty ? 'campaigns' : vendor.tags.join(', ')}.';
    _profession = vendor.vendorTypeName;
    _tagline = _taglineFor(type);
    _response = 'Responds in 1 hour';
    _projects = vendor.ratingCount < 20 ? vendor.ratingCount * 3 : vendor.ratingCount;
    if (_projects < 24) {
      _projects = 24 + vendor.id;
    }
    _assisted = (_projects * 0.7).round();
    _years = '${3 + (vendor.id % 4)}+';
    _satisfaction = (vendor.ratingAvg * 20).round().clamp(90, 99);
    _equipment = _equipmentFor(type);
    _specialties = vendor.tags.isEmpty ? _filters.skip(1).take(3).toList() : vendor.tags;
    _packages = [
      if (vendor.startingFrom != null) _Package(label: 'Starting from', price: vendor.startingFrom!),
      _Package(label: 'Half day', price: (vendor.startingFrom ?? 1800) * 1.2),
      _Package(label: 'Full day', price: (vendor.startingFrom ?? 1800) * 1.8),
    ];
  }

  Future<void> _load() async {
    try {
      final payload = await ApiClient().getJson('/app/vendors/${vendor.id}');
      if (!mounted) {
        return;
      }
      setState(() {
        _bio = payload['bio']?.toString().isNotEmpty == true ? payload['bio'].toString() : _bio;
        _profession = payload['profession']?.toString().isNotEmpty == true ? payload['profession'].toString() : _profession;
        _tagline = payload['tagline']?.toString().isNotEmpty == true ? payload['tagline'].toString() : _tagline;
        final minutes = payload['response_minutes'];
        if (minutes is num) {
          _response = minutes <= 60 ? 'Responds in 1 hour' : 'Responds in ${(minutes / 60).ceil()} hours';
        }
        if (payload['completed_sessions'] is num) {
          _projects = (payload['completed_sessions'] as num).toInt();
        }
        if (payload['assisted_sessions'] is num) {
          _assisted = (payload['assisted_sessions'] as num).toInt();
        }
        if (payload['years_experience'] != null) {
          _years = '${payload['years_experience']}+';
        }
        if (payload['client_satisfaction'] is num) {
          _satisfaction = (payload['client_satisfaction'] as num).toInt();
        }
        _equipment = _stringList(payload['equipment']).isEmpty ? _equipment : _stringList(payload['equipment']);
        _specialties = _stringList(payload['specialties']).isEmpty ? _specialties : _stringList(payload['specialties']);
        final filters = _stringList(payload['portfolio_filters']);
        if (filters.isNotEmpty) {
          _filters = filters;
        }
        final shots = (payload['portfolio'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _Shot.fromJson(Map<String, dynamic>.from(item)))
            .where((item) => item.url.isNotEmpty)
            .toList();
        if (shots.isNotEmpty) {
          _shots = shots;
        }
        final packages = (payload['packages'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _Package.fromJson(Map<String, dynamic>.from(item)))
            .toList();
        if (packages.isNotEmpty) {
          _packages = packages;
        }
        _contactPhone = payload['contact_phone']?.toString() ?? _contactPhone;
        _whatsapp = payload['whatsapp']?.toString() ?? _whatsapp;
      });
    } catch (_) {
      // Keep the listing snapshot when the profile API is offline.
    }
  }

  List<_Shot> get _visibleShots {
    if (_filter == 'All') {
      return _shots;
    }
    return _shots.where((item) => item.category == _filter).toList();
  }

  static const _muted = Color(0xFF8E8B84);
  static const _creamText = Color(0xFFC8C3BA);
  static const _verifiedBlue = Color(0xFF4EA3F0);

  @override
  Widget build(BuildContext context) {
    final location = vendor.location.isEmpty ? (vendor.city.isEmpty ? 'Cairo, Egypt' : '${vendor.city}, Egypt') : vendor.location;

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: Stack(
        children: [
          ListView(
            padding: EdgeInsets.only(bottom: booked ? 118 : 108),
            cacheExtent: 1600,
            children: [
              _hero(),
              Padding(
                padding: EdgeInsets.fromLTRB(16, booked ? 0 : 16, 16, 0),
                child: Transform.translate(
                  offset: Offset(0, booked ? -28 : 0),
                  child: _identity(location),
                ),
              ),
              _stats(),
              _tabBar(),
              if (_tab == 'Portfolio') _filterChips(),
              Padding(
                padding: const EdgeInsets.fromLTRB(8, 10, 8, 16),
                child: _tabBody(),
              ),
            ],
          ),
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: booked ? _contactBar() : _bookNowBar(),
          ),
        ],
      ),
    );
  }

  Widget _hero() {
    final cover = (vendor.coverUrl != null && vendor.coverUrl!.isNotEmpty)
        ? vendor.coverUrl!
        : VendorPhotos.cover(vendor.vendorType, vendor.id);

    return SizedBox(
      height: 292,
      child: Stack(
        fit: StackFit.expand,
        children: [
          _photo(cover),
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [Color(0x660D0D0F), Color(0x000D0D0F), Color(0x660D0D0F)],
                stops: [0, 0.38, 1],
              ),
            ),
          ),
          Positioned(
            top: MediaQuery.paddingOf(context).top + 8,
            left: 12,
            right: 12,
            child: Row(
              children: [
                _roundIcon(Icons.chevron_left_rounded, () => Navigator.of(context).pop()),
                const Spacer(),
                _roundIcon(Icons.ios_share_rounded, () {
                  ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile link copied')));
                }),
                const SizedBox(width: 8),
                FavoriteHeart(vendor: vendor, size: 20, circle: true),
              ],
            ),
          ),
          Positioned(
            right: 20,
            top: 108,
            child: Text(
              _tagline,
              textAlign: TextAlign.right,
              style: const TextStyle(
                color: LensColors.cream,
                fontSize: 15,
                height: 1.2,
                fontStyle: FontStyle.italic,
                fontWeight: FontWeight.w400,
              ),
            ),
          ),
          Positioned(
            right: 16,
            bottom: 16,
            child: Material(
              color: const Color(0xB3141518),
              borderRadius: BorderRadius.circular(22),
              child: InkWell(
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: '${vendor.displayName} showreel')),
                ),
                borderRadius: BorderRadius.circular(22),
                child: const Padding(
                  padding: EdgeInsets.fromLTRB(10, 7, 12, 7),
                  child: Row(
                    children: [
                      Icon(Icons.play_arrow_rounded, color: LensColors.cream, size: 18),
                      SizedBox(width: 4),
                      Text('Watch Showreel', style: TextStyle(color: LensColors.cream, fontWeight: FontWeight.w700, fontSize: 12)),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _identity(String location) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _avatar(),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        Flexible(
                          child: Text(
                            vendor.displayName,
                            maxLines: 1,
                            softWrap: false,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, height: 1.15),
                          ),
                        ),
                        if (vendor.verified) ...[
                          const SizedBox(width: 4),
                          const Icon(Icons.verified, color: _verifiedBlue, size: 18),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFF163226),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: const Color(0xFF2E7A4F)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.circle, color: Color(0xFF3DDC84), size: 8),
                        const SizedBox(width: 6),
                        Text(
                          booked ? 'Your vendor' : 'Available this weekend',
                          softWrap: false,
                          style: const TextStyle(color: Color(0xFF7DCEA0), fontSize: 11, fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                _profession,
                maxLines: 1,
                softWrap: false,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 14, fontWeight: FontWeight.w500, height: 1.2),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 15),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      location,
                      maxLines: 1,
                      softWrap: false,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  if (widget.home.on('reviews')) ...[
                    const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 16),
                    const SizedBox(width: 3),
                    Text(
                      vendor.ratingAvg.toStringAsFixed(1),
                      softWrap: false,
                      style: const TextStyle(color: Color(0xFFF5C451), fontSize: 13, fontWeight: FontWeight.w800),
                    ),
                    Text(
                      ' (${vendor.ratingCount} reviews)',
                      maxLines: 1,
                      softWrap: false,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w500),
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 8),
                      child: Text('·', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 16, fontWeight: FontWeight.w700, height: 1)),
                    ),
                  ],
                  const Icon(Icons.bolt_rounded, color: Color(0xFF3DDC84), size: 16),
                  const SizedBox(width: 2),
                  Flexible(
                    child: Text(
                      _response,
                      maxLines: 1,
                      softWrap: false,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _avatar() {
    final size = booked ? 96.0 : 108.0;
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);
    final radius = booked ? size / 2 : 24.0;

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: LensColors.charcoal,
        borderRadius: BorderRadius.circular(radius),
        border: booked ? Border.all(color: LensColors.charcoal, width: 3) : null,
      ),
      clipBehavior: Clip.antiAlias,
      child: LensConfig.useNetwork
          ? Image.network(
              photo,
              width: size,
              height: size,
              fit: BoxFit.cover,
              alignment: Alignment.center,
              filterQuality: FilterQuality.medium,
              gaplessPlayback: true,
              errorBuilder: (context, error, stackTrace) => _avatarFallback(),
            )
          : _avatarFallback(),
    );
  }

  Widget _avatarFallback() {
    return ColoredBox(
      color: LensColors.graphite,
      child: Center(
        child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 22)),
      ),
    );
  }

  Widget _stats() {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      padding: const EdgeInsets.only(top: 16),
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: Color(0xFF2A2D34), width: 1)),
      ),
      child: Row(
        children: [
          _stat('$_projects', 'Projects Completed'),
          _statDivider(),
          _stat('$_assisted', 'Assisted'),
          _statDivider(),
          _stat(_years, 'Years Experience'),
          _statDivider(),
          _stat('$_satisfaction%', 'Client Satisfaction'),
        ],
      ),
    );
  }

  Widget _statDivider() {
    return Container(width: 1, height: 34, color: const Color(0xFF2A2D34));
  }

  Widget _stat(String value, String label) {
    return Expanded(
      child: Column(
        children: [
          Text(value, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, height: 1)),
          const SizedBox(height: 5),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(color: _muted, fontSize: 10, fontWeight: FontWeight.w600, height: 1.15),
          ),
        ],
      ),
    );
  }

  Widget _tabBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(8, 14, 8, 0),
      child: Column(
        children: [
          Row(
            children: [
              for (final tab in _tabs)
                Expanded(
                  child: GestureDetector(
                    onTap: () => setState(() => _tab = tab),
                    behavior: HitTestBehavior.opaque,
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Text(
                        tab,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: tab == _tab ? LensColors.primary : const Color(0xFF9A9A9A),
                          fontWeight: tab == _tab ? FontWeight.w600 : FontWeight.w500,
                          fontSize: 13.5,
                          height: 1.2,
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
          SizedBox(
            height: 3,
            child: Stack(
              alignment: Alignment.bottomCenter,
              children: [
                const Align(
                  alignment: Alignment.bottomCenter,
                  child: Divider(height: 1, thickness: 1, color: Color(0xFF3A3A3E)),
                ),
                Row(
                  children: [
                    for (final tab in _tabs)
                      Expanded(
                        child: Center(
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 180),
                            height: 3,
                            width: tab == _tab ? 36 : 0,
                            decoration: BoxDecoration(
                              color: LensColors.primary,
                              borderRadius: BorderRadius.circular(99),
                            ),
                          ),
                        ),
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

  Widget _filterChips() {
    return SizedBox(
      height: 50,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 2),
        scrollDirection: Axis.horizontal,
        itemCount: _filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final filter = _filters[index];
          final selected = filter == _filter;
          return GestureDetector(
            onTap: () => setState(() => _filter = filter),
            child: Container(
              alignment: Alignment.center,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.transparent,
                borderRadius: BorderRadius.circular(22),
                border: Border.all(
                  color: selected ? LensColors.primary : const Color(0xFF4A4A4E),
                  width: selected ? 1.8 : 1.15,
                ),
              ),
              child: Text(
                filter,
                style: TextStyle(
                  color: selected ? const Color(0xFFF2EFE9) : const Color(0xFFD0CBC3),
                  fontWeight: FontWeight.w500,
                  fontSize: 13,
                  height: 1.1,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _tabBody() {
    return switch (_tab) {
      'About' => Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8),
          child: Text(_bio, style: const TextStyle(color: _creamText, height: 1.45, fontSize: 14)),
        ),
      'Specialties' => Wrap(
          spacing: 8,
          runSpacing: 8,
          children: _specialties.map((item) => _chip(item)).toList(),
        ),
      'Equipment' => Wrap(
          spacing: 8,
          runSpacing: 8,
          children: _equipment.map((item) => _chip(item)).toList(),
        ),
      'Packages' => Column(
          children: _packages
              .map(
                (item) => Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFF141518),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFF2A2D34)),
                  ),
                  child: Row(
                    children: [
                      Expanded(child: Text(item.label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700))),
                      Text(_money(item.price), style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800)),
                    ],
                  ),
                ),
              )
              .toList(),
        ),
      'Reviews' => Column(
          children: [
            Row(
              children: [
                const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 18),
                const SizedBox(width: 4),
                Text(
                  '${vendor.ratingAvg.toStringAsFixed(1)} from ${vendor.ratingCount} reviews',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                ),
              ],
            ),
            const SizedBox(height: 12),
            const Text(
              'Clients highlight on-time delivery, art direction, and a calm on-set presence.',
              style: TextStyle(color: _creamText, height: 1.4),
            ),
          ],
        ),
      _ => _masonry(),
    };
  }

  Widget _masonry() {
    final items = _visibleShots;
    if (items.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(horizontal: 8),
        child: Text('No work in this collection yet.', style: TextStyle(color: _muted)),
      );
    }

    if (items.length >= 3) {
      final rest = items.skip(3).toList();
      return Column(
        children: [
          SizedBox(
            height: 228,
            child: Row(
              children: [
                Expanded(flex: 6, child: _tile(items[0], padded: false, caption: false)),
                const SizedBox(width: 5),
                Expanded(
                  flex: 5,
                  child: Column(
                    children: [
                      Expanded(child: _tile(items[1], padded: false, caption: false)),
                      const SizedBox(height: 5),
                      Expanded(child: _tile(items[2], padded: false, caption: false)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          if (rest.isNotEmpty) ...[
            const SizedBox(height: 5),
            _twoCol(rest),
          ],
        ],
      );
    }

    return _twoCol(items);
  }

  Widget _twoCol(List<_Shot> items) {
    final left = <_Shot>[];
    final right = <_Shot>[];
    for (var i = 0; i < items.length; i++) {
      (i.isEven ? left : right).add(items[i]);
    }
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: Column(children: [for (final shot in left) _tile(shot, height: shot.tall ? 176 : 124)])),
        const SizedBox(width: 5),
        Expanded(child: Column(children: [for (final shot in right) _tile(shot, height: shot.tall ? 176 : 124)])),
      ],
    );
  }

  Widget _tile(_Shot shot, {double? height, bool padded = true, bool caption = true}) {
    final image = ClipRRect(
      borderRadius: BorderRadius.circular(10),
      child: Stack(
        fit: StackFit.expand,
        children: [
          _photo(shot.url),
          if (caption && shot.title.isNotEmpty)
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.center,
                  end: Alignment.bottomCenter,
                  colors: [Colors.transparent, Color(0xB30D0D0F)],
                ),
              ),
            ),
          if (caption && shot.title.isNotEmpty)
            Positioned(
              left: 10,
              right: 10,
              bottom: 8,
              child: Text(
                shot.title,
                style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 12, height: 1.15),
              ),
            ),
        ],
      ),
    );
    final sized = height == null ? image : SizedBox(height: height, width: double.infinity, child: image);
    return Padding(
      padding: EdgeInsets.only(bottom: padded ? 5 : 0),
      child: sized,
    );
  }

  Widget _photo(String url) {
    if (!LensConfig.useNetwork || url.isEmpty) {
      return const ColoredBox(color: Color(0xFF1C1D22));
    }
    return Image.network(
      url,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => const ColoredBox(color: Color(0xFF1C1D22)),
    );
  }

  Widget _chip(String label) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF2A2D34)),
      ),
      child: Text(label, style: const TextStyle(color: _creamText, fontWeight: FontWeight.w700, fontSize: 13)),
    );
  }

  Widget _roundIcon(IconData icon, VoidCallback onTap, {Color color = LensColors.cream}) {
    return Material(
      color: const Color(0x73141518),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(width: 38, height: 38, child: Icon(icon, color: color, size: 20)),
      ),
    );
  }

  Widget _bookNowBar() {
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0x000D0D0F), Color(0xCC0D0D0F), Color(0xF20D0D0F)],
        ),
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 28, 16, 12),
          child: SizedBox(
            height: 54,
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () => Navigator.of(context).push(
                MaterialPageRoute<void>(builder: (_) => BookDatePage(vendor: vendor, home: widget.home)),
              ),
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: const StadiumBorder(),
              ),
              icon: const Icon(Icons.shopping_bag_outlined, size: 20),
              label: const Text('Book Now', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            ),
          ),
        ),
      ),
    );
  }

  Widget _contactBar() {
    return ColoredBox(
      color: LensColors.charcoal,
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
          child: Row(
            children: [
              Expanded(
                child: _contactButton(
                  label: 'Call Now',
                  icon: Icons.call_outlined,
                  background: const Color(0xFF111111),
                  foreground: Colors.white,
                  border: Colors.white,
                  onTap: _call,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _contactButton(
                  label: 'WhatsApp',
                  icon: Icons.chat,
                  background: const Color(0xFF128C7E),
                  foreground: Colors.white,
                  border: const Color(0xFF128C7E),
                  onTap: _whatsApp,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _contactButton(
                  label: 'Chat in App',
                  icon: Icons.chat_bubble_outline_rounded,
                  background: LensColors.primary,
                  foreground: Colors.white,
                  border: LensColors.primary,
                  onTap: _chat,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _contactButton({
    required String label,
    required IconData icon,
    required Color background,
    required Color foreground,
    required Color border,
    required VoidCallback onTap,
  }) {
    return SizedBox(
      height: 50,
      child: Material(
        color: background,
        shape: StadiumBorder(side: BorderSide(color: border, width: 1.6)),
        child: InkWell(
          customBorder: const StadiumBorder(),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(icon, color: foreground, size: 17),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: foreground, fontSize: 12, fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _call() async {
    final opened = await ContactLaunch.open(ContactLaunch.tel(_phone));
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Call $_phone')));
    }
  }

  Future<void> _whatsApp() async {
    final opened = await ContactLaunch.open(ContactLaunch.whatsapp(_whatsappNumber));
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('WhatsApp $_whatsappNumber')));
    }
  }

  void _chat() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => VendorChatPage(
          vendor: vendor,
          location: vendor.location.isEmpty ? vendor.city : vendor.location,
          status: booked ? 'Confirmed' : 'Pending',
        ),
      ),
    );
  }
}

class _Shot {
  const _Shot({required this.url, required this.title, required this.category, required this.tall});

  factory _Shot.fromJson(Map<String, dynamic> json) {
    return _Shot(
      url: json['url']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      category: json['category']?.toString() ?? 'All',
      tall: json['tall'] == true,
    );
  }

  final String url;
  final String title;
  final String category;
  final bool tall;
}

class _Package {
  const _Package({required this.label, required this.price});

  factory _Package.fromJson(Map<String, dynamic> json) {
    return _Package(
      label: json['label']?.toString() ?? 'Package',
      price: json['price'] is num ? (json['price'] as num).toDouble() : double.tryParse('${json['price']}') ?? 0,
    );
  }

  final String label;
  final double price;
}

List<_Shot> _shotsFor(VendorCard vendor) {
  final urls = VendorPhotos.shots(vendor.vendorType, vendor.id);
  final filters = _filtersFor(vendor.vendorType).where((item) => item != 'All').toList();
  final titles = _titlesFor(vendor.vendorType);
  return [
    for (var i = 0; i < urls.length; i++)
      _Shot(
        url: urls[i],
        title: titles[i % titles.length],
        category: filters[i % filters.length],
        tall: i % 3 == 0,
      ),
  ];
}

List<String> _filtersFor(String slug) {
  return switch (slug) {
    'food_stylist' => ['All', 'Food Photography', 'Recipe Videos', 'Brand Campaigns', 'Behind the Scenes'],
    'videographer' || 'reels' => ['All', 'Events', 'Commercial', 'Corporate', 'Behind the Scenes'],
    'studio' => ['All', 'Cyclorama', 'Kitchen Set', 'Lifestyle', 'Behind the Scenes'],
    'model' => ['All', 'Editorial', 'Runway', 'Lookbook', 'Behind the Scenes'],
    'ugc' => ['All', 'Social', 'Product', 'Lifestyle', 'Behind the Scenes'],
    _ => ['All', 'Weddings', 'Product', 'Portrait', 'Behind the Scenes'],
  };
}

List<String> _titlesFor(String slug) {
  return switch (slug) {
    'food_stylist' => ['Fresh & Real', 'Pasta Perfection', 'BTS On Set', 'Brand Campaign', 'Recipe Frame'],
    'videographer' || 'reels' => ['Showreel Cut', 'Event Night', 'Brand Film', 'BTS On Set'],
    'studio' => ['Cyclorama Wall', 'Daylight Corner', 'Kitchen Set', 'BTS On Set'],
    _ => ['Editorial Frame', 'Campaign Still', 'Portrait Study', 'BTS On Set'],
  };
}

String _taglineFor(String slug) {
  return switch (slug) {
    'food_stylist' => 'Good Food\nBetter Stories',
    'photographer' => 'Light.\nThen Story.',
    'videographer' || 'reels' => 'Frame the\nMoment.',
    'studio' => 'Space to\nCreate.',
    _ => 'Find. Book.\nCreate.',
  };
}

List<String> _equipmentFor(String slug) {
  return switch (slug) {
    'food_stylist' => ['Prop library', 'Surface boards', 'Steam kit', 'Natural light'],
    'videographer' || 'reels' => ['Sony FX3', 'Gimbal', 'LED panels', 'Wireless lavs'],
    'studio' => ['Cyclorama', 'Kitchen set', 'Strobes', 'Tethering'],
    'model' => ['Lookbook kits', 'Editorial posing', 'Runway walk'],
    _ => ['Canon R5', '85mm f/1.2', 'Godox strobes', 'Color grading'],
  };
}

List<String> _stringList(Object? value) {
  if (value is! List) {
    return const [];
  }
  return value.map((item) => '$item').where((item) => item.isNotEmpty).toList();
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
