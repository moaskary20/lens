import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_review_page.dart';
import 'package:lens/features/home/filter_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class BookProjectPage extends StatefulWidget {
  const BookProjectPage({
    super.key,
    required this.vendor,
    required this.home,
    required this.dateLabel,
    required this.time,
    required this.packageName,
    required this.packageDetails,
  });

  final VendorCard vendor;
  final HomeData home;
  final String dateLabel;
  final String time;
  final String packageName;
  final String packageDetails;

  @override
  State<BookProjectPage> createState() => _BookProjectPageState();
}

class _BookProjectPageState extends State<BookProjectPage> {
  static const _types = [
    'Brand Campaign',
    'Food Campaign',
    'Product',
    'Editorial',
    'Social Content',
    'Event',
    'Menu / F&B',
    'Personal',
  ];

  final _name = TextEditingController();
  final _brief = TextEditingController();
  final _notes = TextEditingController();
  String? _type;
  String? _location;
  late List<String> _images;

  VendorCard get vendor => widget.vendor;

  @override
  void initState() {
    super.initState();
    final shots = VendorPhotos.shots(vendor.vendorType, vendor.id);
    _images = shots.take(3).toList();
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
                  const Text(
                    'Give your creator enough context to prepare for your session.',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.35),
                  ),
                  const SizedBox(height: 18),
                  _label('Project Name'),
                  _input(
                    controller: _name,
                    icon: Icons.calendar_today_outlined,
                    hint: 'E.g. Summer Menu Campaign',
                  ),
                  const SizedBox(height: 14),
                  _label('Project Type'),
                  _select(
                    icon: Icons.widgets_outlined,
                    value: _type,
                    hint: 'Select project type',
                    onTap: () => _pick('Select project type', _types, _type, (value) => _type = value),
                  ),
                  const SizedBox(height: 14),
                  _label('Location'),
                  _select(
                    icon: Icons.location_on_outlined,
                    value: _location,
                    hint: 'Select location',
                    onTap: () => _pick('Select location', CategoryFilters.cityOptions, _location, (value) => _location = value),
                  ),
                  const SizedBox(height: 14),
                  _label('Short Brief'),
                  _area(
                    controller: _brief,
                    icon: Icons.description_outlined,
                    hint: 'Tell us about your project, goals, and what you have in mind...',
                  ),
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
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => BookReviewPage(
                        vendor: vendor,
                        home: widget.home,
                        dateLabel: widget.dateLabel,
                        time: widget.time,
                        packageName: widget.packageName,
                        packageDetails: widget.packageDetails,
                        location: _location ?? '',
                        brief: _brief.text,
                        images: _images,
                        projectName: _name.text,
                      ),
                    ),
                  ),
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
              if (widget.home.on('reviews')) ...[
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
          for (var i = 0; i < _images.length; i++) ...[
            _thumb(_images[i], () => setState(() => _images.removeAt(i))),
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

  void _addImage() {
    final pool = VendorPhotos.shots(vendor.vendorType, vendor.id + _images.length + 1);
    final next = pool.firstWhere((url) => !_images.contains(url), orElse: () => pool.first);
    setState(() => _images.add(next));
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
