import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/core/egypt_phone.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/book_draft.dart';

class BookPaymentPage extends StatefulWidget {
  const BookPaymentPage({
    super.key,
    required this.method,
    required this.home,
    this.existing,
    required this.amountLabel,
  });

  final String method;
  final HomeData home;
  final BookPaymentDetails? existing;
  final String amountLabel;

  @override
  State<BookPaymentPage> createState() => _BookPaymentPageState();
}

class _BookPaymentPageState extends State<BookPaymentPage> {
  final _holder = TextEditingController();
  final _number = TextEditingController();
  final _expiry = TextEditingController();
  final _cvv = TextEditingController();
  final _phone = TextEditingController();
  final _paypalEmail = TextEditingController();
  final _paypalName = TextEditingController();
  String? _telecom;
  String? _error;

  String get _method => widget.method;

  String get _title => switch (_method) {
        'wallet' => 'Mobile wallet',
        'paypal' => 'PayPal',
        _ => 'Bank card',
      };

  String get _subtitle => switch (_method) {
        'wallet' => 'Pay from Vodafone Cash, Orange Cash, e& cash, or WE Pay.',
        'paypal' => 'Enter the PayPal account that will pay this booking.',
        _ => 'Enter a Visa, Mastercard, or Meeza card. The number is not stored on Lens.',
      };

  List<(String, String)> get _telecoms {
    final remote = widget.home.telecomWallets
        .map((item) => (item['id']?.toString() ?? '', item['label']?.toString() ?? ''))
        .where((item) => item.$1.isNotEmpty && item.$2.isNotEmpty)
        .toList();
    if (remote.isNotEmpty) {
      return remote;
    }
    return const [
      ('vodafone', 'Vodafone Cash'),
      ('orange', 'Orange Cash'),
      ('etisalat', 'e& cash'),
      ('we', 'WE Pay'),
    ];
  }

  @override
  void initState() {
    super.initState();
    final existing = widget.existing;
    if (existing == null || existing.method != _method) {
      return;
    }
    _holder.text = existing.cardHolder;
    _expiry.text = existing.cardExpiry;
    _phone.text = existing.walletPhone;
    _telecom = existing.walletTelecom;
    _paypalEmail.text = existing.paypalEmail;
    _paypalName.text = existing.paypalName;
  }

  @override
  void dispose() {
    _holder.dispose();
    _number.dispose();
    _expiry.dispose();
    _cvv.dispose();
    _phone.dispose();
    _paypalEmail.dispose();
    _paypalName.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(4, 4, 8, 4),
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Back',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 32),
                  ),
                  Expanded(
                    child: Text(
                      _title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                  Text(_subtitle, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 14, height: 1.4)),
                  const SizedBox(height: 8),
                  Text(
                    'Amount held in escrow  •  ${widget.amountLabel}',
                    style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 13),
                  ),
                  const SizedBox(height: 18),
                  if (_method == 'card') _cardFields(),
                  if (_method == 'wallet') _walletFields(),
                  if (_method == 'paypal') _paypalFields(),
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                  ],
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
              child: SizedBox(
                height: 54,
                width: double.infinity,
                child: FilledButton(
                  onPressed: _save,
                  style: FilledButton.styleFrom(
                    backgroundColor: LensColors.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: const StadiumBorder(),
                  ),
                  child: Text('Save $_title', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _cardFields() {
    return Column(
      children: [
        _field(key: const Key('card-holder'), controller: _holder, hint: 'Cardholder name', icon: Icons.person_outline, capitalization: TextCapitalization.words),
        const SizedBox(height: 10),
        _field(
          key: const Key('card-number'),
          controller: _number,
          hint: 'Card number',
          icon: Icons.credit_card,
          keyboard: TextInputType.number,
          formatters: [_CardNumberFormatter()],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _field(
                key: const Key('card-expiry'),
                controller: _expiry,
                hint: 'MM/YY',
                icon: Icons.event_outlined,
                keyboard: TextInputType.number,
                formatters: [_ExpiryFormatter()],
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _field(
                key: const Key('card-cvv'),
                controller: _cvv,
                hint: 'CVV',
                icon: Icons.lock_outline,
                keyboard: TextInputType.number,
                obscure: true,
                formatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(4)],
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _walletFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _field(
          key: const Key('wallet-phone'),
          controller: _phone,
          hint: 'Wallet phone 010 / 011 / 012 / 015',
          icon: Icons.phone_iphone_rounded,
          keyboard: TextInputType.phone,
          formatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(11)],
        ),
        const SizedBox(height: 16),
        const Text('Telecom wallet', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final item in _telecoms)
              GestureDetector(
                onTap: () => setState(() => _telecom = item.$2),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: _telecom == item.$2 ? const Color(0xFF2A160E) : const Color(0xFF141416),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: _telecom == item.$2 ? LensColors.primary : const Color(0xFF2A2A2E)),
                  ),
                  child: Text(
                    item.$2,
                    style: TextStyle(
                      color: _telecom == item.$2 ? Colors.white : const Color(0xFFD0CBC3),
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _paypalFields() {
    return Column(
      children: [
        _field(key: const Key('paypal-email'), controller: _paypalEmail, hint: 'PayPal email', icon: Icons.alternate_email, keyboard: TextInputType.emailAddress),
        const SizedBox(height: 10),
        _field(key: const Key('paypal-name'), controller: _paypalName, hint: 'PayPal account name', icon: Icons.badge_outlined, capitalization: TextCapitalization.words),
      ],
    );
  }

  Widget _field({
    Key? key,
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboard = TextInputType.text,
    TextCapitalization capitalization = TextCapitalization.none,
    bool obscure = false,
    List<TextInputFormatter> formatters = const [],
  }) {
    return TextField(
      key: key,
      controller: controller,
      keyboardType: keyboard,
      textCapitalization: capitalization,
      obscureText: obscure,
      inputFormatters: formatters,
      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFF6B6B70), fontWeight: FontWeight.w500),
        prefixIcon: Icon(icon, color: const Color(0xFF8E8B84)),
        filled: true,
        fillColor: const Color(0xFF141416),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFF2A2A2E))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: LensColors.primary)),
      ),
    );
  }

  void _save() {
    final details = switch (_method) {
      'card' => _cardDetails(),
      'wallet' => _walletDetails(),
      'paypal' => _paypalDetails(),
      _ => null,
    };
    if (details == null) {
      return;
    }
    Navigator.of(context).pop(details);
  }

  BookPaymentDetails? _cardDetails() {
    final holder = _holder.text.trim();
    final digits = _number.text.replaceAll(RegExp(r'\D'), '');
    final expiryDigits = _expiry.text.replaceAll(RegExp(r'\D'), '');
    final expiry = expiryDigits.length == 4 ? '${expiryDigits.substring(0, 2)}/${expiryDigits.substring(2)}' : _expiry.text.trim();
    final cvv = _cvv.text.trim();
    if (holder.isEmpty) {
      setState(() => _error = 'Enter the cardholder name.');
      return null;
    }
    if (digits.length < 16) {
      setState(() => _error = 'Enter a 16-digit card number.');
      return null;
    }
    if (!RegExp(r'^(0[1-9]|1[0-2])/\d{2}$').hasMatch(expiry)) {
      setState(() => _error = 'Enter expiry as MM/YY.');
      return null;
    }
    if (cvv.length < 3) {
      setState(() => _error = 'Enter the CVV.');
      return null;
    }
    return BookPaymentDetails(
      method: 'card',
      cardHolder: holder,
      cardBrand: _brand(digits),
      cardLast4: digits.substring(digits.length - 4),
      cardExpiry: expiry,
    );
  }

  BookPaymentDetails? _walletDetails() {
    if (!EgyptPhone.isValid(_phone.text, required: true)) {
      setState(() => _error = EgyptPhone.message);
      return null;
    }
    if ((_telecom ?? '').isEmpty) {
      setState(() => _error = 'Choose a telecom wallet.');
      return null;
    }
    return BookPaymentDetails(
      method: 'wallet',
      walletPhone: EgyptPhone.digits(_phone.text),
      walletTelecom: _telecom!,
    );
  }

  BookPaymentDetails? _paypalDetails() {
    final email = _paypalEmail.text.trim();
    final name = _paypalName.text.trim();
    if (!email.contains('@') || !email.contains('.')) {
      setState(() => _error = 'Enter a valid PayPal email.');
      return null;
    }
    if (name.isEmpty) {
      setState(() => _error = 'Enter the PayPal account name.');
      return null;
    }
    return BookPaymentDetails(method: 'paypal', paypalEmail: email, paypalName: name);
  }

  String _brand(String digits) {
    if (digits.startsWith('4')) {
      return 'Visa';
    }
    if (digits.startsWith('5')) {
      return 'Mastercard';
    }
    if (digits.startsWith('50') || digits.startsWith('67')) {
      return 'Meeza';
    }
    return 'Card';
  }
}

class _CardNumberFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final digits = newValue.text.replaceAll(RegExp(r'\D'), '');
    final clipped = digits.length > 16 ? digits.substring(0, 16) : digits;
    final buffer = StringBuffer();
    for (var i = 0; i < clipped.length; i++) {
      if (i > 0 && i % 4 == 0) {
        buffer.write(' ');
      }
      buffer.write(clipped[i]);
    }
    final text = buffer.toString();
    return TextEditingValue(text: text, selection: TextSelection.collapsed(offset: text.length));
  }
}

class _ExpiryFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final digits = newValue.text.replaceAll(RegExp(r'\D'), '');
    final clipped = digits.length > 4 ? digits.substring(0, 4) : digits;
    final text = clipped.length <= 2 ? clipped : '${clipped.substring(0, 2)}/${clipped.substring(2)}';
    return TextEditingValue(text: text, selection: TextSelection.collapsed(offset: text.length));
  }
}
