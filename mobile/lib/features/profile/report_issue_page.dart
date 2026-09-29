import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';

void openReportIssue(BuildContext context) {
  Navigator.of(context).push(
    PageRouteBuilder<void>(
      transitionDuration: const Duration(milliseconds: 560),
      reverseTransitionDuration: const Duration(milliseconds: 340),
      pageBuilder: (context, animation, secondaryAnimation) => const ReportIssuePage(),
      transitionsBuilder: (context, animation, secondaryAnimation, child) {
        final curved = CurvedAnimation(parent: animation, curve: Curves.easeOutCubic, reverseCurve: Curves.easeInCubic);
        return FadeTransition(
          opacity: curved,
          child: SlideTransition(
            position: Tween<Offset>(begin: const Offset(0, 0.06), end: Offset.zero).animate(curved),
            child: ScaleTransition(scale: Tween<double>(begin: 0.96, end: 1).animate(curved), child: child),
          ),
        );
      },
    ),
  );
}

class ReportIssuePage extends StatefulWidget {
  const ReportIssuePage({super.key});

  @override
  State<ReportIssuePage> createState() => _ReportIssuePageState();
}

class _ReportIssuePageState extends State<ReportIssuePage> with TickerProviderStateMixin {
  static const _topics = [
    ('booking', Icons.calendar_today_outlined, 'Booking'),
    ('payment', Icons.payments_outlined, 'Payment'),
    ('account', Icons.person_outline_rounded, 'Account'),
    ('app', Icons.bug_report_outlined, 'App bug'),
    ('creator', Icons.photo_camera_outlined, 'Creator'),
    ('other', Icons.more_horiz_rounded, 'Other'),
  ];

  final _subject = TextEditingController();
  final _body = TextEditingController();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _booking = TextEditingController();
  final _api = ApiClient();

  late final AnimationController _enter;
  late final AnimationController _sentMotion;
  String? _topic;
  bool _sending = false;
  String? _reference;
  String? _error;

  bool get _signedIn => SessionStore.instance.account != null;
  bool get _live => LensConfig.useNetwork;
  bool get _canSend {
    if (_sending || _topic == null) {
      return false;
    }
    if (_subject.text.trim().isEmpty || _body.text.trim().length < 8) {
      return false;
    }
    if (!_signedIn && (_name.text.trim().isEmpty || !_email.text.contains('@'))) {
      return false;
    }
    return true;
  }

  @override
  void initState() {
    super.initState();
    _enter = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..forward();
    _sentMotion = AnimationController(vsync: this, duration: const Duration(milliseconds: 720));
    final account = SessionStore.instance.account;
    if (account != null) {
      _name.text = account.name;
      _email.text = account.email;
      _phone.text = account.phone ?? '';
    }
    _subject.addListener(_refresh);
    _body.addListener(_refresh);
    _name.addListener(_refresh);
    _email.addListener(_refresh);
  }

  void _refresh() {
    if (mounted) {
      setState(() {});
    }
  }

  @override
  void dispose() {
    _enter.dispose();
    _sentMotion.dispose();
    _subject.dispose();
    _body.dispose();
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _booking.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    if (!_canSend) {
      return;
    }
    setState(() {
      _sending = true;
      _error = null;
    });
    var reference = 'ISS-${DateTime.now().millisecondsSinceEpoch.toString().substring(8)}';
    if (_live) {
      try {
        final payload = await _api.postJson('/app/issues', {
          'topic': _topic,
          'subject': _subject.text.trim(),
          'body': _body.text.trim(),
          'name': _name.text.trim(),
          'email': _email.text.trim(),
          'phone': _phone.text.trim().isEmpty ? null : _phone.text.trim(),
          'booking_reference': _booking.text.trim().isEmpty ? null : _booking.text.trim(),
          'app_version': '1.0.0',
          'platform': _platform,
          'guest': !_signedIn,
        });
        reference = payload['reference']?.toString() ?? reference;
      } catch (error) {
        if (!mounted) {
          return;
        }
        setState(() {
          _sending = false;
          _error = error is ApiException ? error.message : 'Could not send the report.';
        });
        return;
      }
    }
    if (!mounted) {
      return;
    }
    setState(() {
      _sending = false;
      _reference = reference;
    });
    _sentMotion.forward(from: 0);
  }

  String get _platform {
    if (kIsWeb) {
      return 'web';
    }
    return switch (defaultTargetPlatform) {
      TargetPlatform.iOS => 'ios',
      TargetPlatform.android => 'android',
      _ => defaultTargetPlatform.name.toLowerCase(),
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: Stack(
        children: [
          const _AmbientGlow(),
          SafeArea(
            child: Column(
              children: [
                _header(),
                Expanded(
                  child: AnimatedSwitcher(
                    duration: const Duration(milliseconds: 420),
                    switchInCurve: Curves.easeOutCubic,
                    child: _reference == null ? _form() : _success(),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _header() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(4, 2, 12, 4),
      child: Row(
        children: [
          IconButton(
            tooltip: 'Back',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
          ),
          const Expanded(
            child: Text('Report an issue', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
          ),
        ],
      ),
    );
  }

  Widget _form() {
    return Column(
      key: const ValueKey('form'),
      children: [
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
            children: [
              _Reveal(animation: _enter, start: 0, child: _hero()),
              const SizedBox(height: 22),
              _Reveal(
                animation: _enter,
                start: 0.08,
                child: const Text('What is this about?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
              ),
              const SizedBox(height: 12),
              _Reveal(animation: _enter, start: 0.12, child: _topicsGrid()),
              const SizedBox(height: 20),
              _Reveal(animation: _enter, start: 0.22, child: _field(label: 'Subject', hint: 'Short summary', controller: _subject, maxLength: 120)),
              const SizedBox(height: 14),
              _Reveal(
                animation: _enter,
                start: 0.28,
                child: _field(label: 'Tell us what happened', hint: 'Steps, booking ref, or a screenshot description…', controller: _body, maxLines: 5, maxLength: 2000),
              ),
              if (_topic == 'booking') ...[
                const SizedBox(height: 14),
                _Reveal(animation: _enter, start: 0.32, child: _field(label: 'Booking reference (optional)', hint: 'LN-1001', controller: _booking)),
              ],
              if (!_signedIn) ...[
                const SizedBox(height: 14),
                _Reveal(animation: _enter, start: 0.36, child: _field(label: 'Your name', hint: 'So we know who to reply to', controller: _name)),
                const SizedBox(height: 14),
                _Reveal(animation: _enter, start: 0.4, child: _field(label: 'Email', hint: 'name@email.com', controller: _email, keyboard: TextInputType.emailAddress)),
                const SizedBox(height: 14),
                _Reveal(animation: _enter, start: 0.44, child: _field(label: 'Phone (optional)', hint: '01xxxxxxxxx', controller: _phone, keyboard: TextInputType.phone)),
              ],
              if (_error != null) ...[
                const SizedBox(height: 14),
                Text(_error!, style: const TextStyle(color: Color(0xFFFF8A7A), fontWeight: FontWeight.w700)),
              ],
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 16),
          child: SizedBox(
            height: 54,
            width: double.infinity,
            child: FilledButton(
              onPressed: _canSend ? _send : null,
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                disabledBackgroundColor: const Color(0xFF4A2414),
                disabledForegroundColor: const Color(0x99FFFFFF),
                foregroundColor: Colors.white,
                elevation: 0,
                shape: const StadiumBorder(),
              ),
              child: _sending
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Stack(
                      alignment: Alignment.center,
                      children: [
                        Text('Send report', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                        Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 26)),
                      ],
                    ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _hero() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF2A150E), Color(0xFF120E0C)],
        ),
        border: Border.all(color: const Color(0x66FF5A1F)),
        boxShadow: [
          BoxShadow(color: LensColors.primary.withValues(alpha: 0.18), blurRadius: 28, spreadRadius: -6),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              color: LensColors.primary,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [BoxShadow(color: LensColors.primary.withValues(alpha: 0.45), blurRadius: 16)],
            ),
            child: const Icon(Icons.flag_rounded, color: Colors.white),
          ),
          const SizedBox(width: 14),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Lens support', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                SizedBox(height: 4),
                Text(
                  'Every ticket lands in the admin desk. We reply in the app when we have an update.',
                  style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 13, height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _topicsGrid() {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        for (final topic in _topics)
          _TopicChip(
            icon: topic.$2,
            label: topic.$3,
            selected: _topic == topic.$1,
            onTap: () => setState(() => _topic = topic.$1),
          ),
      ],
    );
  }

  Widget _field({
    required String label,
    required String hint,
    required TextEditingController controller,
    int maxLines = 1,
    int? maxLength,
    TextInputType? keyboard,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14)),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          maxLines: maxLines,
          maxLength: maxLength,
          keyboardType: keyboard,
          style: const TextStyle(color: Colors.white, fontSize: 15),
          cursorColor: LensColors.primary,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: Color(0xFF6F6C66)),
            filled: true,
            fillColor: const Color(0xFF161412),
            counterStyle: const TextStyle(color: Color(0xFF6F6C66), fontSize: 11),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(16),
              borderSide: const BorderSide(color: LensColors.primary, width: 1.4),
            ),
          ),
        ),
      ],
    );
  }

  Widget _success() {
    return AnimatedBuilder(
      key: const ValueKey('success'),
      animation: _sentMotion,
      builder: (context, _) {
        final t = Curves.easeOutBack.transform(_sentMotion.value.clamp(0.0, 1.0));
        final glow = _sentMotion.value < 0.45 ? _sentMotion.value / 0.45 : 1 - ((_sentMotion.value - 0.45) / 0.55) * 0.28;
        return ListView(
          padding: const EdgeInsets.fromLTRB(28, 28, 28, 32),
          children: [
            Center(
              child: Transform.scale(
                scale: 0.7 + 0.3 * t,
                child: Container(
                  width: 108,
                  height: 108,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: const Color(0xFF14100C),
                    border: Border.all(color: const Color(0xFFFF7A3D), width: 3),
                    boxShadow: [
                      BoxShadow(color: LensColors.primary.withValues(alpha: 0.55 * glow), blurRadius: 22 + 18 * glow, spreadRadius: 2),
                    ],
                  ),
                  child: const Icon(Icons.check_rounded, color: Colors.white, size: 48),
                ),
              ),
            ),
            const SizedBox(height: 22),
            const Text(
              'Ticket sent.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w800, height: 1.1),
            ),
            const SizedBox(height: 10),
            Text(
              'Support has $_reference. We will follow up in your Lens inbox.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 15, height: 1.4),
            ),
            const SizedBox(height: 28),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF161412),
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0x33FF5A1F)),
              ),
              child: Column(
                children: [
                  _meta('Ticket', _reference ?? '—'),
                  const SizedBox(height: 10),
                  _meta('Topic', _topics.firstWhere((item) => item.$1 == _topic, orElse: () => ('other', Icons.more_horiz, 'Other')).$3),
                  const SizedBox(height: 10),
                  _meta('Subject', _subject.text.trim()),
                ],
              ),
            ),
            const SizedBox(height: 28),
            SizedBox(
              height: 54,
              child: FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                style: FilledButton.styleFrom(
                  backgroundColor: LensColors.primary,
                  foregroundColor: Colors.white,
                  shape: const StadiumBorder(),
                ),
                child: const Text('Back to profile', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _meta(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 72, child: Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w700))),
        Expanded(child: Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14))),
      ],
    );
  }
}

class _TopicChip extends StatelessWidget {
  const _TopicChip({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          width: 108,
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF3A1A10) : const Color(0xFF161412),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E), width: selected ? 1.4 : 1),
            boxShadow: selected ? [BoxShadow(color: LensColors.primary.withValues(alpha: 0.22), blurRadius: 16)] : null,
          ),
          child: Column(
            children: [
              Icon(icon, color: selected ? LensColors.primary : const Color(0xFFD0CBC3), size: 22),
              const SizedBox(height: 8),
              Text(
                label,
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: selected ? Colors.white : const Color(0xFFD0CBC3),
                  fontWeight: FontWeight.w800,
                  fontSize: 12.5,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Reveal extends StatelessWidget {
  const _Reveal({required this.animation, required this.start, required this.child});

  final Animation<double> animation;
  final double start;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final curved = CurvedAnimation(
      parent: animation,
      curve: Interval(start.clamp(0, 0.75), (start + 0.34).clamp(0.2, 1), curve: Curves.easeOutCubic),
    );
    return FadeTransition(
      opacity: curved,
      child: SlideTransition(
        position: Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(curved),
        child: child,
      ),
    );
  }
}

class _AmbientGlow extends StatelessWidget {
  const _AmbientGlow();

  @override
  Widget build(BuildContext context) {
    return const IgnorePointer(
      child: Stack(
        children: [
          Positioned(top: -70, right: -40, child: _Blob(size: 220, color: Color(0x33FF5A1F))),
          Positioned(top: 280, left: -90, child: _Blob(size: 180, color: Color(0x18FF5A1F))),
          Positioned(bottom: 60, right: -70, child: _Blob(size: 160, color: Color(0x14FF5A1F))),
        ],
      ),
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(shape: BoxShape.circle, color: color),
    );
  }
}
