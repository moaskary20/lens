import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/booked_vendors_store.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/contact_launch.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/home/book_date_page.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/vendor_chat_page.dart';
import 'package:lens/features/home/vendor_project_page.dart';
import 'package:lens/features/shell/app_shell.dart';
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
  late List<_PortfolioProject> _projectsGallery;
  late String _bio;
  late String _profession;
  late String _tagline;
  late String _response;
  late int _projects;
  late int _assisted;
  late String _years;
  late int _satisfaction;
  late List<String> _equipment;
  List<_EquipmentDetail> _equipmentDetails = const [];
  late List<String> _specialties;
  late List<_Package> _packages;
  List<_SocialStat> _social = const [];
  List<_ReviewItem> _reviews = const [];
  late VendorCard _profile;
  var _loadedFromApi = false;
  var _loadingProfile = false;
  String _contactPhone = '';
  String _whatsapp = '';
  final _tabScroll = ScrollController();

  VendorCard get vendor => _profile;
  bool get booked => widget.booked;
  bool get _guest => SessionStore.instance.isGuest;
  bool get _hasBooking => booked || BookedVendorsStore.instance.has(vendor.id);
  bool get _revealName => _hasBooking;
  String get _visibleName => _revealName ? vendor.displayName : vendor.publicName;
  String get _visibleBio => _revealName ? _bio : _bio.replaceAll(vendor.displayName, vendor.maskedName);

  String get _phone {
    if (_contactPhone.isNotEmpty) {
      return _contactPhone.replaceAll(RegExp(r'\D'), '');
    }
    return '';
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
    _profile = widget.vendor;
    _tab = 'Portfolio';
    _hydrateFromCard();
    if (LensConfig.useNetwork) {
      _loadingProfile = true;
      _projectsGallery = const [];
      _load();
    }
  }

  @override
  void dispose() {
    _tabScroll.dispose();
    super.dispose();
  }

  void _hydrateFromCard() {
    final type = vendor.vendorType;
    _filters = _filtersFor(type);
    _filter = 'All';
    _projectsGallery = _projectsFor(vendor);
    _bio = '${vendor.displayName} is a ${vendor.vendorTypeName.toLowerCase()} based in ${vendor.city.isEmpty ? 'Cairo' : vendor.city}, creating booked work across ${vendor.tags.isEmpty ? 'campaigns' : vendor.tags.join(', ')}.';
    _profession = vendor.vendorTypeName;
    _tagline = _taglineFor(type);
    _response = 'Responds in 1 hour';
    _projects = vendor.completedSessions;
    _assisted = vendor.assistedSessions;
    _years = '${vendor.yearsExperience}+';
    _satisfaction = vendor.clientSatisfaction;
    _equipment = _equipmentFor(type);
    _equipmentDetails = const [];
    _specialties = vendor.tags.isEmpty ? _filters.skip(1).take(3).toList() : vendor.tags;
    _packages = [
      if ((vendor.hourlyPrice ?? 0) > 0)
        _Package(label: 'Price per hour per location', price: vendor.hourlyPrice!),
      if ((vendor.halfDayPrice ?? 0) > 0)
        _Package(label: 'Half-day price (6 hours)', price: vendor.halfDayPrice!),
      if ((vendor.fullDayPrice ?? 0) > 0)
        _Package(label: 'Full-day price (12 hours)', price: vendor.fullDayPrice!),
      if ((vendor.perVideoPrice ?? 0) > 0)
        _Package(label: 'Price per video', price: vendor.perVideoPrice!),
      if (_packagesEmptyFallback(vendor))
        _Package(label: 'Starting from', price: vendor.startingFrom!),
    ];
  }

  static bool _packagesEmptyFallback(VendorCard vendor) {
    return (vendor.hourlyPrice ?? 0) <= 0 &&
        (vendor.halfDayPrice ?? 0) <= 0 &&
        (vendor.fullDayPrice ?? 0) <= 0 &&
        (vendor.perVideoPrice ?? 0) <= 0 &&
        vendor.startingFrom != null;
  }

  Future<void> _load() async {
    try {
      final payload = await ApiClient().getJson('/app/vendors/${widget.vendor.id}');
      if (!mounted) {
        return;
      }
      setState(() {
        _applyApiProfile(payload);
        _loadingProfile = false;
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      // Keep the listing snapshot when the profile API is offline — never flash stock shots.
      setState(() {
        _loadingProfile = false;
        if (_projectsGallery.isEmpty) {
          _projectsGallery = const [];
        }
      });
    }
  }

  void _applyApiProfile(Map<String, dynamic> payload) {
    _loadedFromApi = true;
    _profile = VendorCard.fromJson(payload);
    _bio = payload['bio']?.toString().isNotEmpty == true
        ? payload['bio'].toString()
        : _bio;
    _profession = payload['profession']?.toString().isNotEmpty == true
        ? payload['profession'].toString()
        : (_profile.vendorTypeName.isNotEmpty ? _profile.vendorTypeName : _profession);
    _tagline = payload['tagline']?.toString().isNotEmpty == true
        ? payload['tagline'].toString()
        : _tagline;
    final minutes = payload['response_minutes'];
    if (minutes is num) {
      final value = minutes.toInt();
      if (value <= 0) {
        _response = 'Responds soon';
      } else if (value <= 60) {
        _response = 'Responds in 1 hour';
      } else {
        _response = 'Responds in ${(value / 60).ceil()} hours';
      }
    }
    _projects = int.tryParse('${payload['completed_sessions']}') ?? _profile.completedSessions;
    _assisted = int.tryParse('${payload['assisted_sessions']}') ?? _profile.assistedSessions;
    final years = int.tryParse('${payload['years_experience']}') ?? _profile.yearsExperience;
    _years = '$years+';
    _satisfaction = payload['client_satisfaction'] is num
        ? (payload['client_satisfaction'] as num).toInt()
        : _profile.clientSatisfaction;
    _equipment = _stringList(payload['equipment']);
    _equipmentDetails = (payload['equipment_details'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _EquipmentDetail.fromJson(Map<String, dynamic>.from(item)))
        .where((item) => item.label.isNotEmpty && item.value.isNotEmpty)
        .toList();
    _specialties = _stringList(payload['specialties']);
    final filters = _stringList(payload['portfolio_filters']);
    _filters = filters.isNotEmpty ? filters : const ['All'];
    if (!_filters.contains(_filter)) {
      _filter = _filters.first;
    }
    final apiProjects = (payload['projects'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _PortfolioProject.fromJson(Map<String, dynamic>.from(item)))
        .where((item) => item.coverUrl.isNotEmpty || item.files.isNotEmpty)
        .toList();
    if (apiProjects.isNotEmpty) {
      _projectsGallery = apiProjects;
    } else {
      final legacy = (payload['portfolio'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => _PortfolioProject.fromLegacyShot(Map<String, dynamic>.from(item)))
          .where((item) => item.coverUrl.isNotEmpty)
          .toList();
      // Prefer real portfolio/API work only — no stock placeholder swap after load.
      _projectsGallery = legacy;
    }
    _packages = (payload['packages'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _Package.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    _reviews = (payload['reviews'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _ReviewItem.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    _contactPhone = payload['contact_phone']?.toString() ?? '';
    _whatsapp = payload['whatsapp']?.toString() ?? '';
    final social = (payload['social'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => _SocialStat.fromJson(Map<String, dynamic>.from(item)))
        .where((item) => item.followers != null)
        .toList();
    if (social.isNotEmpty) {
      _social = social;
    } else {
      _social = [
        if (payload['instagram_followers'] != null)
          _SocialStat(
            label: 'Instagram',
            followers: int.tryParse('${payload['instagram_followers']}'),
          ),
        if (payload['tiktok_followers'] != null)
          _SocialStat(
            label: 'TikTok',
            followers: int.tryParse('${payload['tiktok_followers']}'),
          ),
        if (payload['facebook_followers'] != null)
          _SocialStat(
            label: 'Facebook',
            followers: int.tryParse('${payload['facebook_followers']}'),
          ),
      ].where((item) => item.followers != null).toList();
    }
  }

  List<_PortfolioProject> get _visibleProjects {
    if (_filter == 'All') {
      return _projectsGallery;
    }
    return _projectsGallery.where((item) => item.category == _filter || item.category.isEmpty).toList();
  }

  static const _muted = Color(0xFF8E8B84);
  static const _creamText = Color(0xFFC8C3BA);
  static const _verifiedBlue = Color(0xFF4EA3F0);

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Listenable.merge([
        SessionStore.instance,
        BookedVendorsStore.instance,
      ]),
      builder: (context, _) => _scaffold(),
    );
  }

  Widget _scaffold() {
    final location = vendor.location.isEmpty ? (vendor.city.isEmpty ? 'Cairo, Egypt' : '${vendor.city}, Egypt') : vendor.location;

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      bottomNavigationBar: AppShell.navBar(context, index: 0, withFab: false),
      body: Stack(
        children: [
          ListView(
            padding: EdgeInsets.only(bottom: _hasBooking ? 118 : 108),
            cacheExtent: 1600,
            children: [
              _hero(),
              Padding(
                padding: EdgeInsets.fromLTRB(16, _hasBooking ? 0 : 16, 16, 0),
                child: Transform.translate(
                  offset: Offset(0, _hasBooking ? -28 : 0),
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
            child: _hasBooking ? _contactBar() : _bookNowBar(),
          ),
        ],
      ),
    );
  }

  Widget _hero() {
    final cover = (vendor.coverUrl != null && vendor.coverUrl!.isNotEmpty)
        ? vendor.coverUrl!
        : (_loadedFromApi
            ? ''
            : VendorPhotos.cover(vendor.vendorType, vendor.id));

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
                colors: [Color(0x66000000), Color(0x00000000), Color(0x66000000)],
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
                  MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: '$_visibleName showreel')),
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
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
                      Flexible(
                        child: Text(
                          _visibleName,
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
                        Flexible(
                          child: Text(
                            ' (${vendor.ratingCount} reviews)',
                            maxLines: 1,
                            softWrap: false,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w500),
                          ),
                        ),
                        const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 6),
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
        ),
        if (_social.isNotEmpty) ...[
          SizedBox(height: booked ? 10 : 14),
          _socialFollowers(),
        ],
      ],
    );
  }

  Widget _socialFollowers() {
    _SocialStat? of(String key) {
      for (final item in _social) {
        if (item.label.toLowerCase().contains(key) && item.followers != null) {
          return item;
        }
      }
      return null;
    }

    final items = [
      of('instagram'),
      of('tiktok'),
      of('facebook'),
    ].whereType<_SocialStat>().toList();
    if (items.isEmpty) {
      return const SizedBox.shrink();
    }
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        for (var i = 0; i < items.length; i++) ...[
          if (i > 0) const SizedBox(width: 18),
          Flexible(child: _socialFollowerTile(items[i])),
        ],
      ],
    );
  }

  Widget _socialFollowerTile(_SocialStat item) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        _socialIcon(item.label),
        const SizedBox(width: 8),
        Flexible(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Text(
                _formatFollowers(item.followers!),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  height: 1.1,
                ),
              ),
              const Text(
                'Followers',
                maxLines: 1,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Color(0xFF8E8B84),
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  height: 1.2,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _socialIcon(String label) {
    final key = label.trim().toLowerCase();
    if (key.contains('instagram')) {
      return Container(
        width: 28,
        height: 28,
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          gradient: LinearGradient(
            begin: Alignment.topRight,
            end: Alignment.bottomLeft,
            colors: [Color(0xFFF58529), Color(0xFFDD2A7B), Color(0xFF8134AF), Color(0xFF515BD4)],
          ),
        ),
        child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 15),
      );
    }
    if (key.contains('tiktok')) {
      return Container(
        width: 28,
        height: 28,
        decoration: const BoxDecoration(color: Color(0xFF111111), shape: BoxShape.circle),
        child: const Center(
          child: Text(
            '♪',
            style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800, height: 1),
          ),
        ),
      );
    }
    return Container(
      width: 28,
      height: 28,
      decoration: const BoxDecoration(color: Color(0xFF1877F2), shape: BoxShape.circle),
      child: const Icon(Icons.facebook, color: Colors.white, size: 17),
    );
  }

  String _formatFollowers(int value) {
    if (value >= 1000000) {
      final millions = value / 1000000;
      final text = millions >= 10 || millions == millions.roundToDouble()
          ? millions.round().toString()
          : millions.toStringAsFixed(1);
      return '${text}M';
    }
    if (value >= 1000) {
      final thousands = value / 1000;
      final text = thousands >= 10 || thousands == thousands.roundToDouble()
          ? thousands.round().toString()
          : thousands.toStringAsFixed(1);
      return '${text}K';
    }
    return '$value';
  }

  Widget _avatar() {
    final size = booked ? 96.0 : 108.0;
    final photo = (vendor.profilePhotoUrl != null && vendor.profilePhotoUrl!.isNotEmpty)
        ? vendor.profilePhotoUrl!
        : (_loadedFromApi ? '' : (VendorPhotos.portrait(vendor.vendorType, vendor.id)));
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
      child: LensConfig.useNetwork && photo.isNotEmpty
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
      // Booked identity uses Transform.translate(-28) which leaves empty layout space; pull this up.
      margin: EdgeInsets.fromLTRB(16, _hasBooking ? -18 : 16, 16, 8),
      padding: EdgeInsets.only(top: _hasBooking ? 12 : 16),
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
    return Column(
      children: [
        SizedBox(
          height: 46,
          child: ListView.separated(
            controller: _tabScroll,
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            itemCount: _tabs.length,
            separatorBuilder: (_, __) => const SizedBox(width: 22),
            itemBuilder: (context, index) {
              final tab = _tabs[index];
              final selected = tab == _tab;
              return GestureDetector(
                onTap: () {
                  setState(() => _tab = tab);
                  WidgetsBinding.instance.addPostFrameCallback((_) {
                    Scrollable.ensureVisible(
                      context,
                      alignment: 0.45,
                      duration: const Duration(milliseconds: 220),
                      curve: Curves.easeOut,
                    );
                  });
                },
                behavior: HitTestBehavior.opaque,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    Text(
                      tab,
                      maxLines: 1,
                      softWrap: false,
                      style: TextStyle(
                        color: selected ? LensColors.primary : const Color(0xFF9A9A9A),
                        fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                        fontSize: 14,
                        height: 1.2,
                      ),
                    ),
                    const SizedBox(height: 8),
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      height: 3,
                      width: selected ? 36 : 0,
                      decoration: BoxDecoration(
                        color: LensColors.primary,
                        borderRadius: BorderRadius.circular(99),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
        const Divider(height: 1, thickness: 1, color: Color(0xFF3A3A3E)),
      ],
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
          child: Text(_visibleBio, style: const TextStyle(color: _creamText, height: 1.45, fontSize: 14)),
        ),
      'Specialties' => _specialties.isEmpty
          ? const Padding(
              padding: EdgeInsets.symmetric(horizontal: 8),
              child: Text('No specialties listed yet.', style: TextStyle(color: _muted)),
            )
          : Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _specialties.map((item) => _chip(item)).toList(),
            ),
      'Equipment' => _equipmentDetails.isNotEmpty
          ? Column(
              children: [
                for (final item in _equipmentDetails)
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFF141518),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFF2A2D34)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14)),
                        const SizedBox(height: 6),
                        Text(item.value, style: const TextStyle(color: _creamText, height: 1.4, fontSize: 13.5)),
                      ],
                    ),
                  ),
              ],
            )
          : (_equipment.isEmpty
              ? const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 8),
                  child: Text('No equipment listed yet.', style: TextStyle(color: _muted)),
                )
              : Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _equipment.map((item) => _chip(item)).toList(),
                )),
      'Packages' => _packages.isEmpty
          ? const Padding(
              padding: EdgeInsets.symmetric(horizontal: 8),
              child: Text('No packages listed yet.', style: TextStyle(color: _muted)),
            )
          : Column(
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
          crossAxisAlignment: CrossAxisAlignment.start,
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
            if (_reviews.isEmpty)
              const Text(
                'No reviews yet.',
                style: TextStyle(color: _muted, height: 1.4),
              )
            else
              for (final review in _reviews) ...[
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 10),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFF141518),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFF2A2D34)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              review.client,
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                            ),
                          ),
                          const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 15),
                          const SizedBox(width: 2),
                          Text(
                            '${review.rating}',
                            style: const TextStyle(color: Color(0xFFF5C451), fontWeight: FontWeight.w800),
                          ),
                        ],
                      ),
                      if (review.comment.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Text(
                          review.comment,
                          style: const TextStyle(color: _creamText, height: 1.4, fontSize: 13.5),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
          ],
        ),
      _ => _masonry(),
    };
  }

  Widget _masonry() {
    if (_loadingProfile) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 36),
        child: Center(
          child: SizedBox(
            width: 28,
            height: 28,
            child: CircularProgressIndicator(strokeWidth: 2.4, color: LensColors.primary),
          ),
        ),
      );
    }
    final items = _visibleProjects;
    if (items.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(horizontal: 8),
        child: Text('No work in this collection yet.', style: TextStyle(color: _muted)),
      );
    }

    final left = <_PortfolioProject>[];
    final right = <_PortfolioProject>[];
    for (var i = 0; i < items.length; i++) {
      (i.isEven ? left : right).add(items[i]);
    }
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(child: Column(children: [for (final project in left) _projectTile(project, tall: project.tall)])),
        const SizedBox(width: 8),
        Expanded(child: Column(children: [for (final project in right) _projectTile(project, tall: !project.tall)])),
      ],
    );
  }

  Widget _projectTile(_PortfolioProject project, {required bool tall}) {
    final imageHeight = tall ? 176.0 : 124.0;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: GestureDetector(
        onTap: () => _openProject(project),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                height: imageHeight,
                width: double.infinity,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    _photo(project.coverUrl),
                    if (project.mediaCount > 1)
                      Positioned(
                        right: 8,
                        top: 8,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xCC000000),
                            borderRadius: BorderRadius.circular(99),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.collections_rounded, color: Colors.white, size: 12),
                              const SizedBox(width: 4),
                              Text(
                                '${project.mediaCount}',
                                style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        ),
                      ),
                    if (project.videoCount > 0)
                      const Positioned(
                        left: 8,
                        bottom: 8,
                        child: Icon(Icons.play_circle_fill_rounded, color: Colors.white, size: 22),
                      ),
                  ],
                ),
              ),
            ),
            if (project.title.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                project.title,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 13, height: 1.2),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _openProject(_PortfolioProject project) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => VendorProjectPage(
          title: project.title.isEmpty ? 'Project' : project.title,
          description: project.description,
          completedOn: project.completedOn,
          externalUrl: project.externalUrl,
          files: project.files,
        ),
      ),
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
    final guest = _guest;
    return DecoratedBox(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0x00000000), Color(0xCC000000), Color(0xF2000000)],
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
              onPressed: guest ? _openLogin : _openBook,
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: const StadiumBorder(),
              ),
              icon: Icon(guest ? Icons.login_rounded : Icons.shopping_bag_outlined, size: 20),
              label: Text(guest ? 'Log In' : 'Book Now', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            ),
          ),
        ),
      ),
    );
  }

  void _openBook() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => BookDatePage(vendor: vendor, home: widget.home)),
    );
  }

  Future<void> _openLogin() async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (routeContext) => Scaffold(
          backgroundColor: LensColors.charcoal,
          body: Stack(
            children: [
              LoginView(
                bootstrap: widget.home.registerCatalog,
                onSuccess: () => Navigator.of(routeContext).pop(),
              ),
              SafeArea(
                child: IconButton(
                  tooltip: 'Back',
                  onPressed: () => Navigator.of(routeContext).pop(),
                  icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
                ),
              ),
            ],
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
    if (_phone.isEmpty) {
      await _load();
    }
    if (_phone.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Vendor phone is not available yet.')),
        );
      }
      return;
    }
    final opened = await ContactLaunch.open(ContactLaunch.tel(_phone));
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Call $_phone')));
    }
  }

  Future<void> _whatsApp() async {
    if (_whatsappNumber.isEmpty) {
      await _load();
    }
    if (_whatsappNumber.isEmpty) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Vendor WhatsApp is not available yet.')),
        );
      }
      return;
    }
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
          status: _hasBooking ? 'Confirmed' : 'Pending',
          phone: _phone.isEmpty ? null : _phone,
          whatsapp: _whatsappNumber.isEmpty ? null : _whatsappNumber,
        ),
      ),
    );
  }
}

class _ReviewItem {
  const _ReviewItem({
    required this.rating,
    required this.comment,
    required this.client,
  });

  factory _ReviewItem.fromJson(Map<String, dynamic> json) {
    return _ReviewItem(
      rating: json['rating'] is num
          ? (json['rating'] as num).toInt()
          : int.tryParse('${json['rating']}') ?? 0,
      comment: json['comment']?.toString() ?? '',
      client: json['client']?.toString() ?? 'Client',
    );
  }

  final int rating;
  final String comment;
  final String client;
}

class _SocialStat {
  const _SocialStat({required this.label, this.followers, this.url});

  factory _SocialStat.fromJson(Map<String, dynamic> json) {
    final raw = json['followers'];
    int? followers;
    if (raw is num) {
      followers = raw.toInt();
    } else if (raw != null) {
      followers = int.tryParse('$raw');
    }
    return _SocialStat(
      label: json['label']?.toString() ?? '',
      followers: followers,
      url: json['url']?.toString(),
    );
  }

  final String label;
  final int? followers;
  final String? url;
}

class _EquipmentDetail {
  const _EquipmentDetail({required this.label, required this.value});

  factory _EquipmentDetail.fromJson(Map<String, dynamic> json) {
    return _EquipmentDetail(
      label: json['label']?.toString() ?? '',
      value: json['value']?.toString() ?? '',
    );
  }

  final String label;
  final String value;
}

class _PortfolioProject {
  const _PortfolioProject({
    required this.id,
    required this.title,
    required this.coverUrl,
    required this.category,
    required this.tall,
    required this.files,
    this.description = '',
    this.completedOn = '',
    this.externalUrl = '',
    this.mediaCount = 0,
    this.photoCount = 0,
    this.videoCount = 0,
  });

  factory _PortfolioProject.fromJson(Map<String, dynamic> json) {
    final files = (json['files'] as List<dynamic>? ?? const [])
        .whereType<Map>()
        .map((item) => VendorProjectFile.fromJson(Map<String, dynamic>.from(item)))
        .toList();
    var cover = json['cover_url']?.toString() ?? '';
    if (cover.isEmpty) {
      for (final file in files) {
        if (file.kind == 'image' && file.url.isNotEmpty) {
          cover = file.url;
          break;
        }
      }
    }
    if (cover.isEmpty && files.isNotEmpty) {
      cover = files.first.url;
    }
    return _PortfolioProject(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      title: json['title']?.toString() ?? 'Featured work',
      coverUrl: cover,
      category: json['category']?.toString() ?? '',
      tall: json['tall'] == true || ((json['id'] is int ? json['id'] as int : 0) % 3 == 0),
      description: json['description']?.toString() ?? '',
      completedOn: json['completed_on']?.toString() ?? '',
      externalUrl: json['external_url']?.toString() ?? '',
      files: files,
      mediaCount: json['media_count'] is num
          ? (json['media_count'] as num).toInt()
          : files.length,
      photoCount: json['photo_count'] is num
          ? (json['photo_count'] as num).toInt()
          : files.where((item) => item.kind == 'image').length,
      videoCount: json['video_count'] is num
          ? (json['video_count'] as num).toInt()
          : files.where((item) => item.kind == 'video').length,
    );
  }

  factory _PortfolioProject.fromLegacyShot(Map<String, dynamic> json) {
    final url = json['url']?.toString() ?? '';
    return _PortfolioProject(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      title: json['title']?.toString() ?? 'Featured work',
      coverUrl: url,
      category: json['category']?.toString() ?? '',
      tall: json['tall'] == true,
      files: url.isEmpty
          ? const []
          : [VendorProjectFile(id: 0, url: url, kind: 'image')],
      mediaCount: url.isEmpty ? 0 : 1,
      photoCount: url.isEmpty ? 0 : 1,
    );
  }

  final int id;
  final String title;
  final String coverUrl;
  final String category;
  final bool tall;
  final String description;
  final String completedOn;
  final String externalUrl;
  final List<VendorProjectFile> files;
  final int mediaCount;
  final int photoCount;
  final int videoCount;
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

List<_PortfolioProject> _projectsFor(VendorCard vendor) {
  final urls = VendorPhotos.shots(vendor.vendorType, vendor.id);
  final filters = _filtersFor(vendor.vendorType).where((item) => item != 'All').toList();
  final titles = _titlesFor(vendor.vendorType);
  return [
    for (var i = 0; i < urls.length; i++)
      _PortfolioProject(
        id: i + 1,
        title: titles[i % titles.length],
        coverUrl: urls[i],
        category: filters[i % filters.length],
        tall: i % 3 == 0,
        files: [VendorProjectFile(id: i + 1, url: urls[i], kind: 'image')],
        mediaCount: 1,
        photoCount: 1,
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
