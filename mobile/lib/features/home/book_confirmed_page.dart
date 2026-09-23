import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:lens/features/home/vendor_chat_page.dart';
import 'package:lens/features/shell/app_shell.dart';

class BookConfirmedPage extends StatelessWidget {
  const BookConfirmedPage({
    super.key,
    required this.vendor,
    required this.home,
    required this.projectName,
    required this.dateLabel,
    required this.time,
    required this.location,
    this.bookingId,
  });

  final VendorCard vendor;
  final HomeData home;
  final String projectName;
  final String dateLabel;
  final String time;
  final String location;
  final int? bookingId;

  String get _title {
    if (projectName.trim().isNotEmpty) {
      return projectName.trim();
    }
    return '${vendor.vendorTypeName} Session';
  }

  String get _role {
    if (vendor.tags.length >= 2) {
      return '${vendor.tags[0]} & ${vendor.tags[1]} ${vendor.vendorTypeName}';
    }
    return vendor.vendorTypeName;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            const Padding(
              padding: EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: Center(child: _LensMark()),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                children: [
                  const SizedBox(height: 8),
                  const _SuccessMark(),
                  const SizedBox(height: 18),
                  const Text(
                    "You're booked.",
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w800, height: 1.1),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Your creator has been notified and is\ngetting ready for your session.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.4),
                  ),
                  const SizedBox(height: 22),
                  _creator(),
                  const SizedBox(height: 10),
                  _session(),
                  const SizedBox(height: 10),
                  _protected(),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              child: Column(
                children: [
                  SizedBox(
                    height: 54,
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => _openBookings(context),
                      style: FilledButton.styleFrom(
                        backgroundColor: LensColors.primary,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: const StadiumBorder(),
                      ),
                      child: const Stack(
                        alignment: Alignment.center,
                        children: [
                          Text('View Booking', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                          Align(
                            alignment: Alignment.centerRight,
                            child: Icon(Icons.chevron_right_rounded, size: 26),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 10),
                  SizedBox(
                    height: 54,
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () => _openChat(context),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.white,
                        side: const BorderSide(color: Color(0xFF3A3A3E), width: 1.2),
                        shape: const StadiumBorder(),
                      ),
                      icon: const Icon(Icons.chat_bubble_outline_rounded, size: 18),
                      label: const Text('Message Creator', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  GestureDetector(
                    onTap: () => Navigator.of(context).popUntil((route) => route.isFirst),
                    child: const Text(
                      'Back to Home',
                      style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openBookings(BuildContext context) {
    if (AppShell.openBookingsTab != null) {
      Navigator.of(context).popUntil((route) => route.isFirst);
      AppShell.showBookings(guestPreview: true);
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (routeContext) => Scaffold(
          backgroundColor: LensColors.charcoal,
          body: BookingsPage(
            home: home,
            asRoute: true,
            onBack: () => Navigator.of(routeContext).pop(),
          ),
        ),
      ),
    );
  }

  void _openChat(BuildContext context) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => VendorChatPage(
          vendor: vendor,
          projectName: projectName,
          dateLabel: dateLabel,
          time: time,
          location: location,
          bookingId: bookingId,
        ),
      ),
    );
  }

  Widget _card({required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
      decoration: BoxDecoration(
        color: const Color(0xFF141416),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2A2E)),
      ),
      child: child,
    );
  }

  Widget _photo({required String url, required double size, required Widget fallback}) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(12)),
      clipBehavior: Clip.antiAlias,
      child: LensConfig.useNetwork
          ? Image.network(url, fit: BoxFit.cover, errorBuilder: (_, __, ___) => fallback)
          : fallback,
    );
  }

  Widget _creator() {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);
    return _card(
      child: Row(
        children: [
          _photo(
            url: photo,
            size: 54,
            fallback: Center(child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800))),
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
                if (home.on('reviews')) ...[
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star_rounded, color: Color(0xFFF5C451), size: 15),
                      const SizedBox(width: 3),
                      Text(
                        '${vendor.ratingAvg.toStringAsFixed(1)}  (${vendor.ratingCount} bookings)',
                        style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _session() {
    final cover = vendor.coverUrl ?? VendorPhotos.cover(vendor.vendorType, vendor.id);
    return _card(
      child: Row(
        children: [
          _photo(url: cover, size: 58, fallback: const ColoredBox(color: LensColors.graphite)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(_title, style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                _line(Icons.calendar_today_outlined, dateLabel),
                const SizedBox(height: 4),
                _line(Icons.access_time_rounded, time),
                const SizedBox(height: 4),
                _line(Icons.location_on_outlined, location),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _line(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, color: const Color(0xFF8E8B84), size: 14),
        const SizedBox(width: 6),
        Expanded(
          child: Text(text, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
        ),
      ],
    );
  }

  Widget _protected() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
      decoration: BoxDecoration(
        color: const Color(0xFF14100C),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFFF6A33), width: 1.4),
        boxShadow: [
          BoxShadow(color: LensColors.primary.withValues(alpha: 0.55), blurRadius: 10),
          BoxShadow(color: LensColors.primary.withValues(alpha: 0.28), blurRadius: 22, spreadRadius: 1),
        ],
      ),
      child: const Row(
        children: [
          _EscrowLockIcon(),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Payment Protected', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                SizedBox(height: 3),
                Text('Funds secured in Lens Escrow.', style: TextStyle(color: Color(0xFFD0CBC3), fontSize: 13)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SuccessMark extends StatefulWidget {
  const _SuccessMark();

  @override
  State<_SuccessMark> createState() => _SuccessMarkState();
}

class _SuccessMarkState extends State<_SuccessMark> with TickerProviderStateMixin {
  late final AnimationController _pop;
  late final AnimationController _burst;
  late final AnimationController _glow;

  @override
  void initState() {
    super.initState();
    _pop = AnimationController(vsync: this, duration: const Duration(milliseconds: 650))..forward();
    _burst = AnimationController(vsync: this, duration: const Duration(milliseconds: 920))..forward();
    _glow = AnimationController(vsync: this, duration: const Duration(milliseconds: 1100))..forward();
  }

  @override
  void dispose() {
    _pop.dispose();
    _burst.dispose();
    _glow.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: Listenable.merge([_pop, _burst, _glow]),
      builder: (context, _) {
        final popT = Curves.easeOutBack.transform(_pop.value.clamp(0.0, 1.0));
        final glowT = _glow.value < 0.4 ? _glow.value / 0.4 : 1 - ((_glow.value - 0.4) / 0.6) * 0.32;
        return SizedBox(
          height: 196,
          child: Stack(
            alignment: Alignment.center,
            children: [
              CustomPaint(
                size: const Size(196, 196),
                painter: _BurstPainter(progress: _burst.value),
              ),
              Transform.scale(
                scale: 0.62 + 0.38 * popT,
                child: Container(
                  width: 112,
                  height: 112,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: const Color(0xFF14100C),
                    border: Border.all(color: const Color(0xFFFF7A3D), width: 3.6),
                    boxShadow: [
                      BoxShadow(
                        color: LensColors.primary.withValues(alpha: 0.85 * glowT),
                        blurRadius: 10 + 10 * glowT,
                      ),
                      BoxShadow(
                        color: LensColors.primary.withValues(alpha: 0.45 + 0.25 * glowT),
                        blurRadius: 28 + 18 * glowT,
                        spreadRadius: 2 + 3 * glowT,
                      ),
                      BoxShadow(
                        color: const Color(0xFFFF8A4C).withValues(alpha: 0.28 + 0.22 * glowT),
                        blurRadius: 52 + 20 * glowT,
                        spreadRadius: 1,
                      ),
                    ],
                  ),
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      Icon(
                        Icons.check_rounded,
                        color: LensColors.primary.withValues(alpha: 0.45 + 0.25 * glowT),
                        size: 70,
                      ),
                      const Icon(Icons.check_rounded, color: Color(0xFFFFC4A0), size: 56),
                    ],
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _BurstPainter extends CustomPainter {
  const _BurstPainter({required this.progress});

  final double progress;

  @override
  void paint(Canvas canvas, Size size) {
    final t = Curves.easeOutCubic.transform(progress.clamp(0.0, 1.0));
    final center = Offset(size.width / 2, size.height / 2);
    final dash = Paint()..strokeCap = StrokeCap.round;
    const count = 12;

    for (var i = 0; i < count; i++) {
      final angle = (i / count) * math.pi * 2 - math.pi / 2;
      final long = i.isEven;
      const origin = 42.0;
      final endInner = long ? 64.0 : 60.0;
      final endOuter = long ? 80.0 : 71.0;
      final inner = origin + (endInner - origin) * t;
      final outer = origin + 10 + (endOuter - origin - 10) * t;
      dash
        ..color = LensColors.primary.withValues(alpha: (t * 1.35).clamp(0.0, 1.0))
        ..strokeWidth = long ? 3.3 : 2.3;
      canvas.drawLine(
        center + Offset(math.cos(angle) * inner, math.sin(angle) * inner),
        center + Offset(math.cos(angle) * outer, math.sin(angle) * outer),
        dash,
      );
    }

    final spark = Paint()..style = PaintingStyle.fill;
    for (var i = 0; i < 16; i++) {
      final angle = (i / 16) * math.pi * 2 + 0.18;
      final dist = 50 + 58 * t;
      final fade = (1 - t).clamp(0.0, 1.0);
      spark.color = const Color(0xFFFF8A4C).withValues(alpha: 0.95 * fade);
      canvas.drawRRect(
        RRect.fromRectAndRadius(
          Rect.fromCenter(
            center: center + Offset(math.cos(angle) * dist, math.sin(angle) * dist),
            width: 5.5 * fade + 1,
            height: 2.4,
          ),
          const Radius.circular(99),
        ),
        spark,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _BurstPainter oldDelegate) => oldDelegate.progress != progress;
}

class _EscrowLockIcon extends StatelessWidget {
  const _EscrowLockIcon();

  @override
  Widget build(BuildContext context) {
    return const SizedBox(width: 38, height: 40, child: CustomPaint(painter: _EscrowLockPainter()));
  }
}

class _EscrowLockPainter extends CustomPainter {
  const _EscrowLockPainter();

  @override
  void paint(Canvas canvas, Size size) {
    final stroke = Paint()
      ..color = LensColors.primary
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2.15
      ..strokeJoin = StrokeJoin.round
      ..strokeCap = StrokeCap.round;
    final w = size.width;
    final h = size.height;
    final shield = Path()
      ..moveTo(w * 0.50, h * 0.06)
      ..cubicTo(w * 0.78, h * 0.06, w * 0.92, h * 0.16, w * 0.92, h * 0.34)
      ..cubicTo(w * 0.92, h * 0.58, w * 0.74, h * 0.80, w * 0.50, h * 0.94)
      ..cubicTo(w * 0.26, h * 0.80, w * 0.08, h * 0.58, w * 0.08, h * 0.34)
      ..cubicTo(w * 0.08, h * 0.16, w * 0.22, h * 0.06, w * 0.50, h * 0.06)
      ..close();
    canvas.drawPath(shield, stroke);
    final lock = Paint()
      ..color = LensColors.primary
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.85
      ..strokeJoin = StrokeJoin.round
      ..strokeCap = StrokeCap.round;
    canvas.drawRRect(RRect.fromLTRBR(w * 0.34, h * 0.46, w * 0.66, h * 0.70, const Radius.circular(2.6)), lock);
    canvas.drawPath(
      Path()
        ..moveTo(w * 0.40, h * 0.46)
        ..cubicTo(w * 0.40, h * 0.32, w * 0.60, h * 0.32, w * 0.60, h * 0.46),
      lock,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
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
          Positioned(top: -2, child: CustomPaint(size: const Size(22, 14), painter: _SunburstPainter())),
          const Padding(
            padding: EdgeInsets.only(top: 8),
            child: Text('lens', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700, letterSpacing: 0.2, height: 1)),
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
