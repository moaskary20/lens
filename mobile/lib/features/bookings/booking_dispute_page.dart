import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';

Future<bool> openBookingDispute(
  BuildContext context, {
  required int bookingId,
  required String reference,
  required String vendorName,
  required String vendorRole,
  required String projectName,
  required String dateLabel,
  required double total,
  String? photo,
  String status = '',
  Map<String, dynamic>? dispute,
}) async {
  final result = await Navigator.of(context).push<bool>(
    PageRouteBuilder<bool>(
      transitionDuration: const Duration(milliseconds: 560),
      reverseTransitionDuration: const Duration(milliseconds: 340),
      pageBuilder: (context, animation, secondaryAnimation) => BookingDisputePage(
        bookingId: bookingId,
        reference: reference,
        vendorName: vendorName,
        vendorRole: vendorRole,
        projectName: projectName,
        dateLabel: dateLabel,
        total: total,
        photo: photo,
        status: status,
        dispute: dispute,
      ),
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
  return result ?? false;
}

class BookingDisputePage extends StatefulWidget {
  const BookingDisputePage({
    super.key,
    required this.bookingId,
    required this.reference,
    required this.vendorName,
    required this.vendorRole,
    required this.projectName,
    required this.dateLabel,
    required this.total,
    this.photo,
    this.status = '',
    this.dispute,
  });

  final int bookingId;
  final String reference;
  final String vendorName;
  final String vendorRole;
  final String projectName;
  final String dateLabel;
  final double total;
  final String? photo;
  final String status;
  final Map<String, dynamic>? dispute;

  @override
  State<BookingDisputePage> createState() => _BookingDisputePageState();
}

class _BookingDisputePageState extends State<BookingDisputePage> with TickerProviderStateMixin {
  static const _kinds = [
    ('dispute', Icons.gavel_rounded, 'Dispute', 'Hold escrow until admin decides.'),
    ('complaint', Icons.report_gmailerrorred_outlined, 'Complaint', 'Flag the session for staff review.'),
  ];
  static const _reasons = [
    (Icons.high_quality_outlined, 'Delivery quality'),
    (Icons.schedule_outlined, 'Late or missing files'),
    (Icons.assignment_outlined, 'Not as briefed'),
    (Icons.payments_outlined, 'Payment / escrow'),
    (Icons.timelapse_rounded, 'Extra hours unpaid'),
    (Icons.more_horiz_rounded, 'Other'),
  ];

  final _details = TextEditingController();
  final _api = ApiClient();
  late final AnimationController _enter;
  late final AnimationController _sentMotion;
  String _kind = 'dispute';
  String? _reason;
  bool _sending = false;
  String? _error;
  Map<String, dynamic>? _opened;
  bool _justOpened = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;
  bool get _closedSession => const {'cancelled', 'failed', 'refunded', 'approved', 'completed'}.contains(widget.status);
  bool get _canSend => !_sending && !_closedSession && _opened == null && _reason != null && _details.text.trim().length >= 8;

  @override
  void initState() {
    super.initState();
    _enter = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..forward();
    _sentMotion = AnimationController(vsync: this, duration: const Duration(milliseconds: 720));
    _opened = widget.dispute;
    if (_opened != null) {
      _sentMotion.value = 1;
    }
    _details.addListener(() {
      if (mounted) {
        setState(() {});
      }
    });
  }

  @override
  void dispose() {
    _enter.dispose();
    _sentMotion.dispose();
    _details.dispose();
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
    final reason = '$_reason. ${_details.text.trim()}';
    var payload = <String, dynamic>{
      'id': widget.bookingId,
      'kind': _kind,
      'kind_label': _kind == 'complaint' ? 'Complaint' : 'Dispute',
      'status': 'open',
      'status_label': 'Open',
      'reason': reason,
      'reference': widget.reference,
    };
    if (_live) {
      try {
        payload = await _api.postJson('/app/bookings/${widget.bookingId}/dispute', {
          'kind': _kind,
          'reason': reason,
        });
      } catch (error) {
        if (!mounted) {
          return;
        }
        setState(() {
          _sending = false;
          _error = error is ApiException ? error.message : 'Could not open the dispute.';
        });
        return;
      }
    }
    if (!mounted) {
      return;
    }
    setState(() {
      _sending = false;
      _justOpened = true;
      _opened = payload;
    });
    _sentMotion.forward(from: 0);
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
                    child: _opened == null ? _form() : _status(),
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
            onPressed: () => Navigator.of(context).pop(_opened != null),
            icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
          ),
          const Expanded(
            child: Text('Disputes', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
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
              const SizedBox(height: 16),
              _Reveal(animation: _enter, start: 0.08, child: _bookingCard()),
              const SizedBox(height: 22),
              _Reveal(
                animation: _enter,
                start: 0.16,
                child: const Text('What do you want to open?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
              ),
              const SizedBox(height: 12),
              _Reveal(animation: _enter, start: 0.2, child: _kindsRow()),
              const SizedBox(height: 22),
              _Reveal(
                animation: _enter,
                start: 0.26,
                child: const Text('Why?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
              ),
              const SizedBox(height: 12),
              _Reveal(animation: _enter, start: 0.3, child: _reasonWrap()),
              const SizedBox(height: 18),
              _Reveal(animation: _enter, start: 0.38, child: _detailsField()),
              if (_closedSession) ...[
                const SizedBox(height: 14),
                const Text(
                  'This session is closed. Lens can no longer open a dispute on it.',
                  style: TextStyle(color: Color(0xFFFF8A7A), fontWeight: FontWeight.w700),
                ),
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
                        Text('Send to Lens support', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
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
        boxShadow: [BoxShadow(color: LensColors.primary.withValues(alpha: 0.18), blurRadius: 28, spreadRadius: -6)],
      ),
      child: const Row(
        children: [
          _HeroMark(),
          SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Hold escrow. Ask Lens.', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                SizedBox(height: 4),
                Text(
                  'The case lands in admin Disputes. Money stays held until staff refund, pay, split, or close.',
                  style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 13, height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bookingCard() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2A2E)),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: SizedBox(
              width: 56,
              height: 56,
              child: widget.photo == null
                  ? const ColoredBox(color: LensColors.graphite, child: Icon(Icons.person, color: LensColors.cream))
                  : Image.network(widget.photo!, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const ColoredBox(color: LensColors.graphite)),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.vendorName, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15)),
                const SizedBox(height: 2),
                Text(widget.projectName, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 13)),
                const SizedBox(height: 4),
                Text('${widget.reference}  ·  ${widget.dateLabel}', style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _kindsRow() {
    return Row(
      children: [
        for (var i = 0; i < _kinds.length; i++) ...[
          if (i > 0) const SizedBox(width: 10),
          Expanded(
            child: _KindCard(
              icon: _kinds[i].$2,
              title: _kinds[i].$3,
              subtitle: _kinds[i].$4,
              selected: _kind == _kinds[i].$1,
              onTap: () => setState(() => _kind = _kinds[i].$1),
            ),
          ),
        ],
      ],
    );
  }

  Widget _reasonWrap() {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: [
        for (final reason in _reasons)
          _Chip(
            icon: reason.$1,
            label: reason.$2,
            selected: _reason == reason.$2,
            onTap: () => setState(() => _reason = reason.$2),
          ),
      ],
    );
  }

  Widget _detailsField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('What happened?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14)),
        const SizedBox(height: 8),
        TextField(
          controller: _details,
          maxLines: 5,
          maxLength: 2000,
          style: const TextStyle(color: Colors.white, fontSize: 15),
          cursorColor: LensColors.primary,
          decoration: InputDecoration(
            hintText: 'Describe the session, files, or payment issue…',
            hintStyle: const TextStyle(color: Color(0xFF6F6C66)),
            filled: true,
            fillColor: const Color(0xFF161412),
            counterStyle: const TextStyle(color: Color(0xFF6F6C66), fontSize: 11),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
            focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: LensColors.primary, width: 1.4)),
          ),
        ),
      ],
    );
  }

  Widget _status() {
    final opened = _opened ?? const <String, dynamic>{};
    final label = opened['kind_label']?.toString() ?? 'Dispute';
    final status = opened['status_label']?.toString() ?? 'Open';
    final reason = opened['reason']?.toString() ?? _details.text.trim();
    final decision = opened['decision_label']?.toString();
    return AnimatedBuilder(
      key: const ValueKey('status'),
      animation: _sentMotion,
      builder: (context, _) {
        final t = Curves.easeOutBack.transform(_sentMotion.value.clamp(0.0, 1.0));
        final glow = _sentMotion.value < 0.45 ? _sentMotion.value / 0.45 : 1 - ((_sentMotion.value - 0.45) / 0.55) * 0.28;
        return ListView(
          padding: const EdgeInsets.fromLTRB(28, 20, 28, 32),
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
                    boxShadow: [BoxShadow(color: LensColors.primary.withValues(alpha: 0.55 * glow), blurRadius: 22 + 18 * glow, spreadRadius: 2)],
                  ),
                  child: const Icon(Icons.balance_rounded, color: Colors.white, size: 44),
                ),
              ),
            ),
            const SizedBox(height: 22),
            Text(
              _justOpened ? 'Sent to admin.' : 'Case on file.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.w800, height: 1.1),
            ),
            const SizedBox(height: 10),
            const Text(
              'Quality & Support will review booking, chat, and escrow before moving money.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Color(0xFFB0ABA3), fontSize: 15, height: 1.4),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFF161412),
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0x33FF5A1F)),
              ),
              child: Column(
                children: [
                  _meta('Booking', widget.reference),
                  const SizedBox(height: 10),
                  _meta('Type', label),
                  const SizedBox(height: 10),
                  _meta('Status', status),
                  if (decision != null && decision.isNotEmpty) ...[
                    const SizedBox(height: 10),
                    _meta('Decision', decision),
                  ],
                  const SizedBox(height: 10),
                  _meta('Reason', reason),
                ],
              ),
            ),
            const SizedBox(height: 28),
            SizedBox(
              height: 54,
              child: FilledButton(
                onPressed: () => Navigator.of(context).pop(true),
                style: FilledButton.styleFrom(backgroundColor: LensColors.primary, foregroundColor: Colors.white, shape: const StadiumBorder()),
                child: const Text('Back to bookings', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
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
        SizedBox(width: 78, child: Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w700))),
        Expanded(child: Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 14))),
      ],
    );
  }
}

class _HeroMark extends StatelessWidget {
  const _HeroMark();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 54,
      height: 54,
      decoration: BoxDecoration(
        color: LensColors.primary,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: LensColors.primary.withValues(alpha: 0.45), blurRadius: 16)],
      ),
      child: const Icon(Icons.balance_rounded, color: Colors.white),
    );
  }
}

class _KindCard extends StatelessWidget {
  const _KindCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
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
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF3A1A10) : const Color(0xFF161412),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E), width: selected ? 1.4 : 1),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(icon, color: selected ? LensColors.primary : const Color(0xFFD0CBC3)),
              const SizedBox(height: 10),
              Text(title, style: TextStyle(color: selected ? Colors.white : const Color(0xFFD0CBC3), fontWeight: FontWeight.w800, fontSize: 14)),
              const SizedBox(height: 4),
              Text(subtitle, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5, height: 1.3)),
            ],
          ),
        ),
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.icon, required this.label, required this.selected, required this.onTap});

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
        borderRadius: BorderRadius.circular(99),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF3A1A10) : const Color(0xFF161412),
            borderRadius: BorderRadius.circular(99),
            border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 16, color: selected ? LensColors.primary : const Color(0xFFD0CBC3)),
              const SizedBox(width: 6),
              Text(label, style: TextStyle(color: selected ? Colors.white : const Color(0xFFD0CBC3), fontWeight: FontWeight.w700, fontSize: 12.5)),
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
