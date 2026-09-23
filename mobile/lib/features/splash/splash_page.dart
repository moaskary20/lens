import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';

class SplashPage extends StatefulWidget {
  const SplashPage({super.key, this.api});

  final ApiClient? api;

  @override
  State<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends State<SplashPage> {
  Timer? _hold;

  @override
  void initState() {
    super.initState();
    _boot();
  }

  @override
  void dispose() {
    _hold?.cancel();
    super.dispose();
  }

  Future<void> _boot() async {
    final started = DateTime.now();
    Map<String, dynamic> payload = DemoBootstrap.payload();
    try {
      final remote = await (widget.api ?? ApiClient()).getJson('/app/bootstrap');
      final types = remote['vendor_types'];
      final popular = remote['popular'];
      final empty = (types is! List || types.isEmpty) && (popular is! List || popular.isEmpty);
      payload = empty ? DemoBootstrap.payload() : remote;
    } catch (_) {
      payload = DemoBootstrap.payload();
    }

    final wait = const Duration(milliseconds: 1400) - DateTime.now().difference(started);
    if (wait > Duration.zero && mounted) {
      final done = Completer<void>();
      _hold = Timer(wait, done.complete);
      await done.future;
    }
    if (!mounted) {
      return;
    }
    Navigator.of(context).pushReplacement(
      MaterialPageRoute<void>(builder: (_) => OnboardingPage(bootstrap: payload)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Color(0xFF050505),
      body: Stack(
        children: [
          Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SizedBox(
                  width: 320,
                  height: 260,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      DecoratedBox(
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: RadialGradient(
                            colors: [
                              Color(0x00000000),
                              Color(0x33FF5A1F),
                              Color(0x77FF5A1F),
                              Color(0x22FF5A1F),
                              Color(0x00000000),
                            ],
                            stops: [0.12, 0.38, 0.52, 0.68, 1],
                          ),
                        ),
                        child: SizedBox.expand(),
                      ),
                      SizedBox(
                        width: 108,
                        height: 108,
                        child: CustomPaint(painter: _AperturePainter()),
                      ),
                    ],
                  ),
                ),
                Transform.translate(
                  offset: Offset(0, -18),
                  child: Text(
                    'Lens',
                    style: TextStyle(
                      color: Color(0xFFF3E6C8),
                      fontSize: 52,
                      fontWeight: FontWeight.w600,
                      height: 1,
                      letterSpacing: 0.4,
                    ),
                  ),
                ),
              ],
            ),
          ),
          Positioned(
            left: 0,
            right: 0,
            bottom: 42,
            child: Text(
              'Create. Connect. Deliver.',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Color(0xFF6B6560),
                fontSize: 13,
                fontWeight: FontWeight.w500,
                letterSpacing: 0.2,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _AperturePainter extends CustomPainter {
  const _AperturePainter();

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    final bounds = Rect.fromCircle(center: center, radius: radius);

    canvas.save();
    canvas.clipPath(Path()..addOval(bounds));

    canvas.drawCircle(
      center,
      radius,
      Paint()
        ..shader = const RadialGradient(
          center: Alignment(-0.38, -0.32),
          radius: 1.15,
          colors: [
            Color(0xFFFFB06A),
            Color(0xFFFF6A2B),
            Color(0xFFFF4A12),
            Color(0xFF8A2A0C),
          ],
          stops: [0, 0.32, 0.62, 1],
        ).createShader(bounds),
    );

    for (var i = 0; i < 6; i++) {
      final start = -0.55 + i * (math.pi / 3);
      final path = Path()
        ..moveTo(center.dx, center.dy)
        ..arcTo(bounds, start, 0.42, false)
        ..close();
      canvas.drawPath(
        path,
        Paint()..color = Color.fromRGBO(0, 0, 0, i.isEven ? 0.16 : 0.08),
      );
    }

    final blade = Paint()
      ..color = const Color(0xFF070707)
      ..strokeWidth = size.width * 0.055
      ..strokeCap = StrokeCap.round
      ..style = PaintingStyle.stroke;
    canvas.drawLine(
      Offset(center.dx + radius * 0.62, center.dy - radius * 0.78),
      Offset(center.dx + radius * 0.08, center.dy + radius * 0.08),
      blade,
    );

    final house = Path()
      ..moveTo(center.dx - radius * 0.46, center.dy + radius * 0.10)
      ..lineTo(center.dx - radius * 0.04, center.dy - radius * 0.36)
      ..lineTo(center.dx + radius * 0.40, center.dy + radius * 0.06)
      ..lineTo(center.dx + radius * 0.40, center.dy + radius * 0.52)
      ..lineTo(center.dx - radius * 0.46, center.dy + radius * 0.52)
      ..close();
    canvas.drawPath(house, Paint()..color = const Color(0xFF050505));

    canvas.restore();

    canvas.drawCircle(
      center,
      radius - 0.6,
      Paint()
        ..style = PaintingStyle.stroke
        ..strokeWidth = 1.8
        ..color = const Color(0x88FF8A4C),
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
