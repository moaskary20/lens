import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/core/vendor_photos.dart';
import 'package:lens/features/home/book_confirmed_page.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_payment_page.dart';
import 'package:lens/features/home/book_project_catalog.dart';

class BookCheckoutPage extends StatefulWidget {
  const BookCheckoutPage({super.key, required this.draft});

  final BookDraft draft;

  @override
  State<BookCheckoutPage> createState() => _BookCheckoutPageState();
}

class _BookCheckoutPageState extends State<BookCheckoutPage> {
  final _promo = TextEditingController();
  String? _promoError;
  String? _payError;
  bool _busy = false;
  double _discount = 0;
  double _quotedTotal = 0;

  BookDraft get draft => widget.draft;
  VendorCard get vendor => draft.vendor;

  double get _session => draft.sessionPrice;
  double get _platform => _session * 0.10;
  double get _tax => 0;
  double get _total => _quotedTotal > 0 ? _quotedTotal : (_session + _platform + _tax - _discount);

  String get _title => draft.projectName.trim();

  @override
  void initState() {
    super.initState();
    _promo.text = draft.promoCode ?? '';
  }

  @override
  void dispose() {
    _promo.dispose();
    super.dispose();
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
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  const Text(
                    'Secure Checkout',
                    style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.1),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Almost there! Review your order and complete your payment.',
                    style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.35),
                  ),
                  const SizedBox(height: 16),
                  _orderCard(),
                  const SizedBox(height: 10),
                  _costs(),
                  const SizedBox(height: 10),
                  _escrow(),
                  const SizedBox(height: 14),
                  _methods(),
                  const SizedBox(height: 14),
                  _promoBox(),
                  if (_payError != null) ...[
                    const SizedBox(height: 10),
                    Text(_payError!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                  ],
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
                      onPressed: _busy ? null : _confirm,
                      style: FilledButton.styleFrom(
                        backgroundColor: LensColors.primary,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: const StadiumBorder(),
                      ),
                      child: const Stack(
                        alignment: Alignment.center,
                        children: [
                          Text('Confirm & Pay', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
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
                        'Your payment is protected with bank-level security.',
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

  Widget _box({required Widget child, EdgeInsets padding = const EdgeInsets.fromLTRB(14, 12, 14, 14)}) {
    return Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: const Color(0xFF141416),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFF2A2A2E)),
      ),
      child: child,
    );
  }

  Widget _orderCard() {
    final photo = vendor.profilePhotoUrl ?? VendorPhotos.portrait(vendor.vendorType, vendor.id);

    return _box(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(color: LensColors.graphite, borderRadius: BorderRadius.circular(14)),
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
                Text(
                  _title.isEmpty ? vendor.displayName : _title,
                  style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 2),
                Row(
                  children: [
                    Flexible(
                      child: Text(
                        vendor.displayName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 13),
                      ),
                    ),
                    if (vendor.verified) ...[
                      const SizedBox(width: 4),
                      const Icon(Icons.verified, color: Color(0xFF3B82F6), size: 14),
                    ],
                  ],
                ),
                if (draft.projectType.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    '${BookProjectCatalog.typeLabel(vendor.vendorType)}  •  ${draft.projectType}',
                    style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 12),
                  ),
                ],
                const SizedBox(height: 6),
                Row(
                  children: [
                    const Icon(Icons.calendar_today_outlined, color: Color(0xFF8E8B84), size: 13),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        '${draft.dateLabel}  •  ${draft.time} (${draft.durationLabel})',
                        style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11.5),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.location_on_outlined, color: Color(0xFF8E8B84), size: 14),
                    const SizedBox(width: 5),
                    Expanded(
                      child: Text(
                        draft.location.isEmpty ? 'No map pin' : draft.location,
                        style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                      ),
                    ),
                  ],
                ),
                if (draft.packageName.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(draft.packageName.trim(), style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                ],
              ],
            ),
          ),
          GestureDetector(
            onTap: () => Navigator.of(context).pop(),
            child: const Row(
              children: [
                Text('Edit', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
                Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84), size: 18),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _initials() {
    return Center(child: Text(vendor.initials, style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 12)));
  }

  Widget _costs() {
    return _box(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Cost Breakdown', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13, fontWeight: FontWeight.w600)),
          const SizedBox(height: 12),
          _costRow(draft.packageName.trim().isEmpty ? 'Session Price' : draft.packageName.trim(), _egp(_session)),
          const SizedBox(height: 10),
          _costRow('Lens Platform Fee (10%)', _egp(_platform), info: 'A 10% platform fee covers booking protection, escrow, and support.'),
          if (_tax > 0) ...[
            const SizedBox(height: 10),
            _costRow('Taxes', _egp(_tax)),
          ],
          if (_discount > 0) ...[
            const SizedBox(height: 10),
            _costRow('Promo discount', '- ${_egp(_discount)}'),
          ],
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1, thickness: 1, color: Color(0xFF2A2A2E)),
          ),
          Row(
            children: [
              const Expanded(child: Text('Total', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800))),
              Text(_egp(_total), style: const TextStyle(color: LensColors.primary, fontSize: 18, fontWeight: FontWeight.w800)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _costRow(String label, String value, {String? info}) {
    return Row(
      children: [
        Expanded(
          child: Row(
            children: [
              Flexible(child: Text(label, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 14))),
              if (info != null) ...[
                const SizedBox(width: 4),
                GestureDetector(
                  onTap: () => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(info))),
                  child: const Icon(Icons.info_outline, color: Color(0xFF6B6B70), size: 15),
                ),
              ],
            ],
          ),
        ),
        Text(value, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 14, fontWeight: FontWeight.w600)),
      ],
    );
  }

  Widget _escrow() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
      decoration: BoxDecoration(
        color: const Color(0xFF14100C),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFFF6A33), width: 1.4),
        boxShadow: [
          BoxShadow(color: LensColors.primary.withValues(alpha: 0.55), blurRadius: 10, spreadRadius: 0),
          BoxShadow(color: LensColors.primary.withValues(alpha: 0.28), blurRadius: 22, spreadRadius: 1),
          BoxShadow(color: const Color(0x66FF5A1F), blurRadius: 32, spreadRadius: -2),
        ],
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _EscrowLockIcon(),
          SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Secure Escrow Hold', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
                SizedBox(height: 4),
                Text(
                  'Your payment is securely held by Lens according to the booking completion process.',
                  style: TextStyle(color: Color(0xFFD0CBC3), fontSize: 12.5, height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _methods() {
    return Column(
      children: [
        Row(
          children: [
            const Expanded(
              child: Text('Payment Method', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
            ),
            const Text('Held in Lens escrow', style: TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w600, fontSize: 12)),
          ],
        ),
        const SizedBox(height: 10),
        _box(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          child: Column(
            children: [
              _methodTile(
                id: 'card',
                leading: const Icon(Icons.credit_card, color: Color(0xFFD0CBC3), size: 22),
                title: 'Bank card',
                subtitle: _methodSummary('card', 'Visa, Mastercard, or Meeza'),
                trailing: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(color: const Color(0xFF1A3A8A), borderRadius: BorderRadius.circular(6)),
                  child: const Text('CARD', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.6)),
                ),
              ),
              const Divider(height: 1, thickness: 1, color: Color(0xFF2A2A2E)),
              _methodTile(
                id: 'wallet',
                leading: const Icon(Icons.account_balance_wallet_outlined, color: Color(0xFFD0CBC3), size: 22),
                title: 'Mobile wallet',
                subtitle: _methodSummary('wallet', 'Vodafone Cash, Orange Cash, e& cash, WE Pay'),
                trailing: const Icon(Icons.phone_iphone_rounded, color: Color(0xFF8E8B84)),
              ),
              const Divider(height: 1, thickness: 1, color: Color(0xFF2A2A2E)),
              _methodTile(
                id: 'paypal',
                leading: const Icon(Icons.payments_outlined, color: Color(0xFFD0CBC3), size: 22),
                title: 'PayPal',
                subtitle: _methodSummary('paypal', 'Pay with your PayPal balance or linked card'),
                trailing: const Text('PayPal', style: TextStyle(color: Color(0xFF003087), fontWeight: FontWeight.w800, fontSize: 12)),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _methodTile({
    required String id,
    required Widget leading,
    required String title,
    required String subtitle,
    required Widget trailing,
  }) {
    final selected = draft.paymentMethod == id;
    return InkWell(
      onTap: () => _openMethod(id),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(
          children: [
            _radio(selected),
            const SizedBox(width: 10),
            leading,
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 2),
                  Text(subtitle, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                ],
              ),
            ),
            trailing,
          ],
        ),
      ),
    );
  }

  Widget _radio(bool selected) {
    return Container(
      width: 20,
      height: 20,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: selected ? LensColors.primary : const Color(0xFF6B6B70), width: 2),
      ),
      child: selected
          ? Center(
              child: Container(
                width: 10,
                height: 10,
                decoration: const BoxDecoration(color: LensColors.primary, shape: BoxShape.circle),
              ),
            )
          : null,
    );
  }

  Widget _promoBox() {
    return _box(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Promo code', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          const Text('Add a Lens offer code before you pay.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _promo,
                  textCapitalization: TextCapitalization.characters,
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, letterSpacing: 0.6),
                  decoration: InputDecoration(
                    hintText: 'WELCOME200',
                    hintStyle: const TextStyle(color: Color(0xFF6B6B70), letterSpacing: 0),
                    prefixIcon: const Icon(Icons.sell_outlined, color: Color(0xFF8E8B84)),
                    filled: true,
                    fillColor: const Color(0xFF0D0D0F),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
                    enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                height: 48,
                child: FilledButton(
                  onPressed: _applyPromo,
                  style: FilledButton.styleFrom(backgroundColor: LensColors.primary, foregroundColor: Colors.white, shape: const StadiumBorder()),
                  child: const Text('Apply', style: TextStyle(fontWeight: FontWeight.w800)),
                ),
              ),
            ],
          ),
          if (_promoError != null) ...[
            const SizedBox(height: 8),
            Text(_promoError!, style: const TextStyle(color: Color(0xFFFF6B6B), fontSize: 12.5, fontWeight: FontWeight.w600)),
          ],
          if (_discount > 0) ...[
            const SizedBox(height: 8),
            Text('Saved ${_egp(_discount)}', style: const TextStyle(color: Color(0xFF7DCE9A), fontWeight: FontWeight.w700)),
          ],
        ],
      ),
    );
  }

  String _methodSummary(String id, String fallback) {
    if (draft.payment?.method == id && (draft.payment?.summary.isNotEmpty ?? false)) {
      return draft.payment!.summary;
    }
    return fallback;
  }

  Future<void> _openMethod(String id) async {
    draft.paymentMethod = id;
    setState(() => _payError = null);
    final result = await Navigator.of(context).push<BookPaymentDetails>(
      MaterialPageRoute(
        builder: (_) => BookPaymentPage(
          method: id,
          home: draft.home,
          existing: draft.payment?.method == id ? draft.payment : null,
          amountLabel: _egp(_total),
        ),
      ),
    );
    if (!mounted) {
      return;
    }
    setState(() {
      if (result != null) {
        draft.payment = result;
        draft.paymentMethod = result.method;
      }
    });
  }

  Future<void> _confirm() async {
    if (!draft.hasPaymentDetails) {
      setState(() => _payError = 'Open ${_methodTitle(draft.paymentMethod)} and enter the payment details first.');
      return;
    }
    final bookingId = await _persistBooking();
    if (!mounted) {
      return;
    }
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => BookConfirmedPage(
          vendor: vendor,
          home: draft.home,
          projectName: _title,
          dateLabel: draft.dateLabel,
          time: draft.time,
          location: draft.location,
          bookingId: bookingId,
        ),
      ),
    );
  }

  String _methodTitle(String id) => switch (id) {
        'wallet' => 'Mobile wallet',
        'paypal' => 'PayPal',
        _ => 'Bank card',
      };

  Future<void> _applyPromo() async {
    final code = _promo.text.trim();
    draft.promoCode = code.isEmpty ? null : code;
    if (code.isEmpty) {
      setState(() {
        _discount = 0;
        _quotedTotal = 0;
        _promoError = null;
      });
      return;
    }
    try {
      final payload = await ApiClient().postJson('/app/bookings/quote', {
        'vendor_id': vendor.id,
        'session_price': _session,
        'promo_code': code,
        'location_text': draft.location,
      });
      if (!mounted) {
        return;
      }
      setState(() {
        _discount = (payload['discount_amount'] as num?)?.toDouble() ?? 0;
        _quotedTotal = (payload['total_paid'] as num?)?.toDouble() ?? 0;
        _promoError = _discount > 0 ? null : 'Code accepted with no extra discount.';
      });
    } catch (_) {
      if (!mounted) {
        return;
      }
      setState(() => _promoError = 'This promo code is not valid.');
    }
  }

  Future<int?> _persistBooking() async {
    draft.promoCode = _promo.text.trim().isEmpty ? null : _promo.text.trim();
    if (!LensConfig.useNetwork || !SessionStore.instance.isClient) {
      return null;
    }
    try {
      final payload = await ApiClient().postJson('/app/bookings', {
        'vendor_id': vendor.id,
        'project_name': _title,
        'project_type': draft.projectType,
        'client_brief': draft.brief,
        'location_text': draft.location,
        'location_lat': draft.latitude,
        'location_lng': draft.longitude,
        'package_type': draft.packageKey,
        'session_price': _session,
        'scheduled_at': draft.scheduledAt?.toIso8601String(),
        'duration_hours': draft.packageKey.contains('full') ? 12 : (draft.packageKey.contains('hour') ? 4 : 6),
        'payment_method': draft.paymentMethod,
        'payment_details': draft.payment?.toApi(),
        'promo_code': draft.promoCode,
        'project_details': draft.details,
        'notes': draft.notes,
      });
      return payload['id'] is int ? payload['id'] as int : int.tryParse('${payload['id']}');
    } catch (error) {
      if (!mounted) {
        return null;
      }
      setState(() => _payError = error.toString());
      return null;
    }
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

class _EscrowLockIcon extends StatelessWidget {
  const _EscrowLockIcon();

  @override
  Widget build(BuildContext context) {
    return const SizedBox(
      width: 38,
      height: 40,
      child: CustomPaint(painter: _EscrowLockPainter()),
    );
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

    final body = RRect.fromLTRBR(w * 0.34, h * 0.46, w * 0.66, h * 0.70, const Radius.circular(2.6));
    canvas.drawRRect(body, lock);

    final shackle = Path()
      ..moveTo(w * 0.40, h * 0.46)
      ..cubicTo(w * 0.40, h * 0.32, w * 0.60, h * 0.32, w * 0.60, h * 0.46);
    canvas.drawPath(shackle, lock);
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
