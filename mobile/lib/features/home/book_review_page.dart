import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_checkout_page.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_project_catalog.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class BookReviewPage extends StatelessWidget {
  const BookReviewPage({super.key, required this.draft});

  final BookDraft draft;

  VendorCard get vendor => draft.vendor;
  HomeData get home => draft.home;
  String get projectName => draft.projectName;
  String get dateLabel => draft.dateLabel;
  String get time => draft.time;

  String get _duration => draft.durationLabel;

  String get _place => draft.location.trim();

  String? get _coords {
    if (draft.latitude == null || draft.longitude == null) {
      return null;
    }
    return '${draft.latitude!.toStringAsFixed(5)}, ${draft.longitude!.toStringAsFixed(5)}';
  }

  String get _briefText => draft.brief.trim();

  String get _role {
    if (vendor.tags.length >= 2) {
      return '${vendor.tags[0]} • ${vendor.tags[1]}';
    }
    return vendor.vendorTypeName;
  }

  double get _base => draft.sessionPrice;

  double get _fee => (_base * 0.10);

  List<String> get _gallery => List<String>.from(draft.images);

  List<(String, String)> get _projectRows {
    final rows = <(String, String)>[];
    if (draft.projectName.trim().isNotEmpty) {
      rows.add(('Project name', draft.projectName.trim()));
    }
    if (draft.projectType.trim().isNotEmpty) {
      rows.add((BookProjectCatalog.typeLabel(vendor.vendorType), draft.projectType.trim()));
    }
    for (final field in BookProjectCatalog.extraFields(vendor.vendorType)) {
      final value = draft.details[field.key]?.trim() ?? '';
      if (value.isNotEmpty) {
        rows.add((field.label, value));
      }
    }
    if (draft.packageName.trim().isNotEmpty) {
      rows.add(('Package', draft.packageName.trim()));
    }
    return rows;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            _header(context),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  const Text(
                    'Review Your Booking',
                    style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.1),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Check the details below and confirm your booking.',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.35),
                  ),
                  const SizedBox(height: 16),
                  _creator(context),
                  const SizedBox(height: 10),
                  _schedule(context),
                  const SizedBox(height: 10),
                  _location(context),
                  const SizedBox(height: 10),
                  _projectCard(context),
                  const SizedBox(height: 10),
                  _briefCard(context),
                  const SizedBox(height: 10),
                  _pricing(),
                  const SizedBox(height: 10),
                  _policy(context),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: Column(
                children: [
                  SizedBox(
                    height: 54,
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute<void>(
                          builder: (_) => BookCheckoutPage(draft: draft),
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
                          Text('Continue to Payment', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                          Align(
                            alignment: Alignment.centerRight,
                            child: Icon(Icons.chevron_right_rounded, size: 26),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.lock_outline, color: Color(0xFF6B6B70), size: 14),
                      SizedBox(width: 6),
                      Text(
                        'Your payment is secure with Lens',
                        style: TextStyle(color: Color(0xFF6B6B70), fontSize: 12),
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

  Widget _header(BuildContext context) {
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

  Widget _card({required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
      decoration: BoxDecoration(
        color: const Color(0xFF141416),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2A2E)),
      ),
      child: child,
    );
  }

  Widget _edit(VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: const Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.edit_outlined, color: Color(0xFF8E8B84), size: 15),
          SizedBox(width: 4),
          Text('Edit', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _creator(BuildContext context) {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);

    return _card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text('Creator', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
              ),
              GestureDetector(
                onTap: () => _popPages(context, 4),
                child: const Row(
                  children: [
                    Text('View Profile', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
                    Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84), size: 18),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: const BoxDecoration(color: LensColors.graphite, shape: BoxShape.circle),
                clipBehavior: Clip.antiAlias,
                child: LensConfig.useNetwork
                    ? Image.network(photo, fit: BoxFit.cover, errorBuilder: (_, __, ___) => _initials())
                    : _initials(),
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
                            style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800),
                          ),
                        ),
                        if (vendor.verified) ...[
                          const SizedBox(width: 4),
                          const Icon(Icons.verified, color: Color(0xFF3B82F6), size: 16),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Text(_role, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12.5)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (home.on('reviews')) ...[
                          const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 15),
                          const SizedBox(width: 3),
                          Text(
                            '${vendor.ratingAvg.toStringAsFixed(1)}  (${vendor.ratingCount} bookings)',
                            style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12),
                          ),
                        ],
                        if (vendor.verified || vendor.ratingAvg >= 4.7) ...[
                          const SizedBox(width: 10),
                          const Icon(Icons.workspace_premium, color: Color(0xFFE8B84A), size: 15),
                          const SizedBox(width: 3),
                          const Text('Top Creator', style: TextStyle(color: Color(0xFFE8B84A), fontSize: 12, fontWeight: FontWeight.w700)),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _initials() {
    return Center(child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800)));
  }

  Widget _schedule(BuildContext context) {
    return _card(
      child: Column(
        children: [
          Row(
            children: [
              const Expanded(child: Text('Schedule', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600))),
              _edit(() => _popPages(context, 3)),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _meta(Icons.calendar_today_outlined, 'Date', dateLabel)),
              Expanded(child: _meta(Icons.access_time_rounded, 'Start Time', time)),
              Expanded(child: _meta(Icons.timer_outlined, 'Duration', _duration)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _meta(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: const Color(0xFF8E8B84), size: 18),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
              const SizedBox(height: 2),
              Text(value, style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w800, height: 1.2)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _location(BuildContext context) {
    return _card(
      child: Column(
        children: [
          Row(
            children: [
              const Expanded(child: Text('Location', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600))),
              _edit(() => _popPages(context, 2)),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _place.isEmpty ? 'No map pin selected' : _place,
                      style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
                    ),
                    Text(
                      _coords == null ? 'Pinned on Google Maps' : 'Google Maps  •  $_coords',
                      style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _projectCard(BuildContext context) {
    final rows = _projectRows;
    return _card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(child: Text('Project', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600))),
              _edit(() => _popPages(context, 2)),
            ],
          ),
          const SizedBox(height: 10),
          if (rows.isEmpty)
            const Text('No project details yet.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13))
          else
            for (var i = 0; i < rows.length; i++) ...[
              if (i > 0) const SizedBox(height: 10),
              _detail(rows[i].$1, rows[i].$2),
            ],
        ],
      ),
    );
  }

  Widget _detail(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 118,
          child: Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5, height: 1.3)),
        ),
        Expanded(
          child: Text(value, style: const TextStyle(color: Colors.white, fontSize: 13.5, fontWeight: FontWeight.w700, height: 1.3)),
        ),
      ],
    );
  }

  Widget _briefCard(BuildContext context) {
    final gallery = _gallery;
    final extra = math.max(0, gallery.length - 3);

    return _card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(child: Text('Project Brief', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600))),
              _edit(() => _popPages(context, 2)),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Padding(
                padding: EdgeInsets.only(top: 2),
                child: Icon(Icons.view_list_outlined, color: Color(0xFF8E8B84), size: 18),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  _briefText.isEmpty ? 'No brief added.' : _briefText,
                  style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13, height: 1.4),
                ),
              ),
            ],
          ),
          if (draft.notes.trim().isNotEmpty) ...[
            const SizedBox(height: 10),
            Text(draft.notes.trim(), style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5, height: 1.35)),
          ],
          if (gallery.isNotEmpty) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                for (var i = 0; i < math.min(3, gallery.length); i++) ...[
                  if (i > 0) const SizedBox(width: 8),
                  Expanded(child: _shot(gallery[i])),
                ],
                if (extra > 0) ...[
                  const SizedBox(width: 8),
                  Expanded(
                    child: Stack(
                      children: [
                        _shot(gallery.length > 3 ? gallery[3] : gallery.last),
                        Positioned.fill(
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              color: const Color(0x990D0D0F),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Center(
                              child: Text(
                                '+$extra\nMore',
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 13, height: 1.15),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _shot(String url) {
    return AspectRatio(
      aspectRatio: 1,
      child: Container(
        decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(12)),
        clipBehavior: Clip.antiAlias,
        child: LensConfig.useNetwork
            ? Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite))
            : const ColoredBox(color: LensColors.graphite),
      ),
    );
  }

  Widget _pricing() {
    return _card(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Pricing Preview', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
          const SizedBox(height: 12),
          Row(
            children: [
              const Icon(Icons.photo_camera_outlined, color: Color(0xFF8E8B84), size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  draft.packageName.trim().isEmpty ? 'Session price' : draft.packageName.trim(),
                  style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 14),
                ),
              ),
              Text(_egp(_base), style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 14, fontWeight: FontWeight.w600)),
            ],
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              const Icon(Icons.sell_outlined, color: Color(0xFF8E8B84), size: 20),
              const SizedBox(width: 8),
              const Expanded(child: Text('Lens platform fee (10%)', style: TextStyle(color: Color(0xFFD0CBC3), fontSize: 14))),
              Text(_egp(_fee), style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 14, fontWeight: FontWeight.w600)),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1, thickness: 1, color: Color(0xFF2A2A2E)),
          ),
          Row(
            children: [
              const Expanded(child: Text('Total', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800))),
              Text(_egp(_base + _fee), style: const TextStyle(color: LensColors.primary, fontSize: 18, fontWeight: FontWeight.w800)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _policy(BuildContext context) {
    return GestureDetector(
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(builder: (_) => const PlaceholderPage(title: 'Cancellation Policy')),
      ),
      child: _card(
        child: const Row(
          children: [
            Icon(Icons.verified_user_outlined, color: Color(0xFF8E8B84), size: 22),
            SizedBox(width: 10),
            Expanded(child: Text('Cancellation Policy', style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700))),
            Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
          ],
        ),
      ),
    );
  }

  void _popPages(BuildContext context, int pages) {
    var i = 0;
    Navigator.of(context).popUntil((_) => i++ >= pages);
  }

  String _egp(double value) {
    final digits = value.round().toString();
    final buffer = StringBuffer('EGP ');
    for (var i = 0; i < digits.length; i++) {
      final remaining = digits.length - i;
      if (i != 0 && remaining % 3 == 0) {
        buffer.write(',');
      }
      buffer.write(digits[i]);
    }
    return buffer.toString();
  }
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
