import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_map_picker_page.dart';
import 'package:lens/features/home/book_project_catalog.dart';
import 'package:lens/features/home/book_review_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class BookProjectPage extends StatefulWidget {
  const BookProjectPage({super.key, required this.draft});

  final BookDraft draft;

  @override
  State<BookProjectPage> createState() => _BookProjectPageState();
}

class _BookProjectPageState extends State<BookProjectPage> {
  final _name = TextEditingController();
  final _brief = TextEditingController();
  final _notes = TextEditingController();
  String? _error;
  late List<BookPriceOption> _prices;
  BookPriceOption? _price;

  BookDraft get draft => widget.draft;
  VendorCard get vendor => draft.vendor;

  String get _typeLabel => BookProjectCatalog.typeLabel(vendor.vendorType);
  List<String> get _typeOptions => BookProjectCatalog.typeOptions(vendor.vendorType);
  List<BookProjectField> get _extras => BookProjectCatalog.extraFields(vendor.vendorType);

  @override
  void initState() {
    super.initState();
    _name.text = draft.projectName;
    _brief.text = draft.brief;
    _notes.text = draft.notes;
    _prices = BookProjectCatalog.pricesFor(vendor);
    _price = _matchPrice(draft.packageKey, _prices);
    final shots = VendorPhotos.shots(vendor.vendorType, vendor.id);
    if (draft.images.isEmpty) {
      draft.images = shots.take(3).toList();
    }
    _loadPrices();
  }

  Future<void> _loadPrices() async {
    try {
      final payload = await ApiClient().getJson('/app/vendors/${vendor.id}');
      final remote = (payload['packages'] as List<dynamic>? ?? const [])
          .whereType<Map>()
          .map((item) => BookPriceOption.fromJson(Map<String, dynamic>.from(item)))
          .where((item) => item.price > 0)
          .toList();
      if (!mounted || remote.isEmpty) {
        return;
      }
      setState(() {
        _prices = remote;
        _price = _matchPrice(_price?.key ?? draft.packageKey, remote);
      });
    } catch (_) {}
  }

  @override
  void dispose() {
    _name.dispose();
    _brief.dispose();
    _notes.dispose();
    super.dispose();
  }

  String get _vendorLocation {
    if (vendor.location.isNotEmpty) {
      return vendor.location;
    }
    return vendor.city.isEmpty ? 'Cairo, Egypt' : '${vendor.city}, Egypt';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            _header(),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 20),
                children: [
                  _vendorCard(),
                  const SizedBox(height: 22),
                  const Text(
                    'Tell them about your project',
                    style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.15),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Fill the ${vendor.vendorTypeName.toLowerCase()} brief, choose a price, and pin the location on Google Maps.',
                    style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.35),
                  ),
                  const SizedBox(height: 18),
                  _label('Project Name *'),
                  _input(
                    controller: _name,
                    icon: Icons.calendar_today_outlined,
                    hint: 'E.g. Summer Menu Campaign',
                  ),
                  const SizedBox(height: 14),
                  _label('$_typeLabel *'),
                  _select(
                    icon: Icons.widgets_outlined,
                    value: draft.projectType.isEmpty ? null : draft.projectType,
                    hint: 'Select $_typeLabel',
                    onTap: () => _pick('Select $_typeLabel', _typeOptions, draft.projectType, (value) => draft.projectType = value),
                  ),
                  for (final field in _extras) ...[
                    const SizedBox(height: 14),
                    _label('${field.label}${field.required ? ' *' : ''}'),
                    _select(
                      icon: Icons.tune_outlined,
                      value: draft.details[field.key],
                      hint: 'Select ${field.label.toLowerCase()}',
                      onTap: () => _pick(field.label, field.options, draft.details[field.key], (value) => draft.details[field.key] = value),
                    ),
                  ],
                  const SizedBox(height: 14),
                  _label('Session price *'),
                  _priceGrid(),
                  const SizedBox(height: 14),
                  _label('Location *'),
                  _select(
                    icon: Icons.location_on_outlined,
                    value: draft.location.isEmpty ? null : draft.location,
                    hint: 'Pin on Google Maps',
                    onTap: _pickLocation,
                  ),
                  const SizedBox(height: 14),
                  _label('Short Brief *'),
                  _area(
                    controller: _brief,
                    icon: Icons.description_outlined,
                    hint: 'Tell us about your project, goals, and what you have in mind...',
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                  ],
                  const SizedBox(height: 16),
                  _referencesHead(),
                  const SizedBox(height: 10),
                  _uploadBox(),
                  const SizedBox(height: 12),
                  _imageRow(),
                  const SizedBox(height: 14),
                  _moodboard(),
                  const SizedBox(height: 16),
                  _label('Additional Notes (Optional)'),
                  _area(
                    controller: _notes,
                    icon: Icons.edit_outlined,
                    hint: 'Any other details, special requests, or important notes...',
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              child: SizedBox(
                height: 54,
                width: double.infinity,
                child: FilledButton(
                  onPressed: _continue,
                  style: FilledButton.styleFrom(
                    backgroundColor: LensColors.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: const StadiumBorder(),
                  ),
                  child: const Stack(
                    alignment: Alignment.center,
                    children: [
                      Text('Review Booking', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      Align(
                        alignment: Alignment.centerRight,
                        child: Icon(Icons.chevron_right_rounded, size: 26),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 4, 8, 4),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 32),
          ),
          const Expanded(child: Center(child: _LensMark())),
          const SizedBox(width: 48),
        ],
      ),
    );
  }

  Widget _vendorCard() {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);

    return Row(
      children: [
        Container(
          width: 72,
          height: 72,
          decoration: BoxDecoration(color: LensColors.charcoal, borderRadius: BorderRadius.circular(16)),
          clipBehavior: Clip.antiAlias,
          child: LensConfig.useNetwork
              ? Image.network(photo, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _photoFallback())
              : _photoFallback(),
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
                      style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800, height: 1.15),
                    ),
                  ),
                  if (vendor.verified) ...[
                    const SizedBox(width: 4),
                    const Icon(Icons.verified, color: Color(0xFF3B82F6), size: 18),
                  ],
                ],
              ),
              const SizedBox(height: 3),
              Text(vendor.vendorTypeName, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 13)),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 14),
                  const SizedBox(width: 2),
                  Expanded(
                    child: Text(
                      _vendorLocation,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5),
                    ),
                  ),
                ],
              ),
              if (draft.home.on('reviews')) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 16),
                    const SizedBox(width: 3),
                    Text(
                      '${vendor.ratingAvg.toStringAsFixed(1)}  (${vendor.ratingCount} reviews)',
                      style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Widget _photoFallback() {
    return ColoredBox(
      color: LensColors.graphite,
      child: Center(child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800))),
    );
  }

  Widget _label(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(text, style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700)),
    );
  }

  InputDecoration _decoration({required IconData icon, required String hint, String? counter}) {
    return InputDecoration(
      prefixIcon: Icon(icon, color: const Color(0xFF8E8B84), size: 20),
      hintText: hint,
      hintStyle: const TextStyle(color: Color(0xFF6B6B70), fontSize: 14, fontWeight: FontWeight.w500),
      counterText: counter,
      counterStyle: const TextStyle(color: Color(0xFF6B6B70), fontSize: 12),
      filled: true,
      fillColor: const Color(0xFF141416),
      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF3A3A3E)),
      ),
    );
  }

  Widget _input({required TextEditingController controller, required IconData icon, required String hint}) {
    return TextField(
      controller: controller,
      style: const TextStyle(color: Colors.white, fontSize: 14),
      decoration: _decoration(icon: icon, hint: hint),
    );
  }

  Widget _area({required TextEditingController controller, required IconData icon, required String hint}) {
    return ValueListenableBuilder<TextEditingValue>(
      valueListenable: controller,
      builder: (context, value, _) {
        return TextField(
          controller: controller,
          minLines: 4,
          maxLines: 6,
          maxLength: 500,
          style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.4),
          decoration: _decoration(icon: icon, hint: hint, counter: '${value.text.length}/500'),
        );
      },
    );
  }

  Widget _select({
    required IconData icon,
    required String? value,
    required String hint,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        height: 52,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: const Color(0xFF141416),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0xFF2A2A2E)),
        ),
        child: Row(
          children: [
            Icon(icon, color: const Color(0xFF8E8B84), size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                value ?? hint,
                style: TextStyle(
                  color: value == null ? const Color(0xFF6B6B70) : Colors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
            const Icon(Icons.keyboard_arrow_down_rounded, color: Color(0xFF8E8B84)),
          ],
        ),
      ),
    );
  }

  Widget _referencesHead() {
    return const Row(
      children: [
        Expanded(
          child: Text('Reference Images', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700)),
        ),
        Flexible(
          child: Text(
            'Add photos that show your vision (optional)',
            textAlign: TextAlign.right,
            style: TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5),
          ),
        ),
      ],
    );
  }

  Widget _uploadBox() {
    return GestureDetector(
      onTap: _addImage,
      child: CustomPaint(
        painter: const _DashPainter(),
        child: const SizedBox(
          height: 92,
          width: double.infinity,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.image_outlined, color: Color(0xFF8E8B84), size: 26),
              SizedBox(height: 6),
              Text('Tap to upload images', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600, fontSize: 13)),
              SizedBox(height: 2),
              Text('JPG, PNG up to 10 MB', style: TextStyle(color: Color(0xFF6B6B70), fontSize: 11.5)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _imageRow() {
    return SizedBox(
      height: 72,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: [
          for (var i = 0; i < draft.images.length; i++) ...[
            _thumb(draft.images[i], () => setState(() => draft.images.removeAt(i))),
            const SizedBox(width: 10),
          ],
          GestureDetector(
            onTap: _addImage,
            child: CustomPaint(
              painter: const _DashPainter(radius: 12),
              child: const SizedBox(
                width: 72,
                height: 72,
                child: Icon(Icons.add, color: Color(0xFF8E8B84), size: 28),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _thumb(String url, VoidCallback onRemove) {
    return SizedBox(
      width: 72,
      height: 72,
      child: Stack(
        children: [
          Positioned.fill(
            child: Container(
              decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(12)),
              clipBehavior: Clip.antiAlias,
              child: LensConfig.useNetwork
                  ? Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite))
                  : const ColoredBox(color: LensColors.graphite),
            ),
          ),
          Positioned(
            top: 4,
            right: 4,
            child: GestureDetector(
              onTap: onRemove,
              child: Container(
                width: 20,
                height: 20,
                decoration: const BoxDecoration(color: Color(0xCC141416), shape: BoxShape.circle),
                child: const Icon(Icons.close, color: Colors.white, size: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _moodboard() {
    return GestureDetector(
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(builder: (_) => const PlaceholderPage(title: 'Lens AI Moodboard')),
      ),
      child: Container(
        padding: const EdgeInsets.fromLTRB(14, 14, 10, 14),
        decoration: BoxDecoration(
          color: const Color(0xFF1A120E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0x55FF5A1F)),
        ),
        child: const Row(
          children: [
            Icon(Icons.auto_awesome, color: LensColors.primary, size: 22),
            SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Attach Lens AI Moodboard (Optional)',
                    style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13.5),
                  ),
                  SizedBox(height: 3),
                  Text(
                    'Generate or upload a moodboard to share the visual direction.',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12, height: 1.3),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
          ],
        ),
      ),
    );
  }

  BookPriceOption? _matchPrice(String key, List<BookPriceOption> options) {
    for (final option in options) {
      if (option.key == key) {
        return option;
      }
    }
    return options.isEmpty ? null : options.first;
  }

  void _addImage() {
    final pool = VendorPhotos.shots(vendor.vendorType, vendor.id + draft.images.length + 1);
    final next = pool.firstWhere((url) => !draft.images.contains(url), orElse: () => pool.first);
    setState(() => draft.images.add(next));
  }

  Widget _priceGrid() {
    if (_prices.isEmpty) {
      return const Text('This vendor has no published prices yet.', style: TextStyle(color: Color(0xFFFF6B6B)));
    }
    return Column(
      children: [
        for (final option in _prices) ...[
          GestureDetector(
            onTap: () => setState(() => _price = option),
            child: Container(
              width: double.infinity,
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              decoration: BoxDecoration(
                color: const Color(0xFF141416),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: _price?.key == option.key ? LensColors.primary : const Color(0xFF2A2A2E)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(option.label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13.5)),
                  ),
                  Text('EGP ${option.price.round()}', style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800)),
                ],
              ),
            ),
          ),
        ],
      ],
    );
  }

  Future<void> _pickLocation() async {
    final result = await Navigator.of(context).push<PickedMapLocation>(
      MaterialPageRoute(
        builder: (_) => BookMapPickerPage(
          initial: draft.latitude != null && draft.longitude != null
              ? PickedMapLocation(label: draft.location, latitude: draft.latitude!, longitude: draft.longitude!)
              : null,
          fallbackLabel: _vendorLocation,
          fallbackLatitude: vendor.mapLatitude,
          fallbackLongitude: vendor.mapLongitude,
        ),
      ),
    );
    if (result == null) {
      return;
    }
    setState(() {
      draft.location = result.label;
      draft.latitude = result.latitude;
      draft.longitude = result.longitude;
    });
  }

  void _continue() {
    final missing = <String>[];
    if (_name.text.trim().isEmpty) {
      missing.add('project name');
    }
    if (draft.projectType.isEmpty) {
      missing.add(_typeLabel.toLowerCase());
    }
    for (final field in _extras.where((item) => item.required)) {
      if ((draft.details[field.key] ?? '').isEmpty) {
        missing.add(field.label.toLowerCase());
      }
    }
    if (_price == null || _price!.price <= 0) {
      missing.add('session price');
    }
    if (draft.location.trim().isEmpty) {
      missing.add('Google Maps location');
    }
    if (_brief.text.trim().isEmpty) {
      missing.add('short brief');
    }
    if (missing.isNotEmpty) {
      setState(() => _error = 'Please enter ${missing.join(', ')}.');
      return;
    }

    draft.projectName = _name.text.trim();
    draft.brief = _brief.text.trim();
    draft.notes = _notes.text.trim();
    draft.packageKey = _price!.key;
    draft.packageName = _price!.label;
    draft.sessionPrice = _price!.price;
    if (_price!.durationHours != null) {
      draft.packageDetails = '${_price!.durationHours} Hours';
    }

    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => BookReviewPage(draft: draft)),
    );
  }

  Future<void> _pick(String title, List<String> options, String? selected, ValueChanged<String> onPick) async {
    final result = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(8, 12, 8, 24),
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
                child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
              ),
              for (final option in options)
                ListTile(
                  title: Text(option, style: const TextStyle(color: Colors.white)),
                  trailing: option == selected ? const Icon(Icons.check, color: LensColors.primary) : null,
                  onTap: () => Navigator.pop(context, option),
                ),
            ],
          ),
        );
      },
    );
    if (result != null) {
      setState(() => onPick(result));
    }
  }
}

class _DashPainter extends CustomPainter {
  const _DashPainter({this.radius = 14});

  final double radius;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFF3A3A3E)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.3;
    final path = Path()
      ..addRRect(RRect.fromRectAndRadius(Offset.zero & size, Radius.circular(radius)));
    for (final metric in path.computeMetrics()) {
      var distance = 0.0;
      const dash = 5.0;
      const gap = 4.0;
      while (distance < metric.length) {
        final next = math.min(distance + dash, metric.length);
        canvas.drawPath(metric.extractPath(distance, next), paint);
        distance += dash + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant _DashPainter oldDelegate) => oldDelegate.radius != radius;
}

class _LensMark extends StatelessWidget {
  const _LensMark();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 36,
      child: Stack(
        alignment: Alignment.topCenter,
        clipBehavior: Clip.none,
        children: [
          Positioned(
            top: -2,
            child: CustomPaint(size: const Size(22, 14), painter: _SunburstPainter()),
          ),
          const Padding(
            padding: EdgeInsets.only(top: 8),
            child: Text(
              'lens',
              style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700, letterSpacing: 0.2, height: 1),
            ),
          ),
        ],
      ),
    );
  }
}

class _SunburstPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = LensColors.primary
      ..strokeWidth = 1.5
      ..strokeCap = StrokeCap.round;
    final origin = Offset(size.width / 2, size.height);
    for (var i = 0; i < 7; i++) {
      final angle = -math.pi + (i * math.pi / 6);
      canvas.drawLine(
        origin + Offset(math.cos(angle) * 2, math.sin(angle) * 2),
        origin + Offset(math.cos(angle) * 9, math.sin(angle) * 9),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
