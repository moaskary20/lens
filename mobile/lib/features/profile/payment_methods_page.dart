import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/book_draft.dart';
import 'package:lens/features/home/book_payment_page.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class PaymentMethodsPage extends StatefulWidget {
  const PaymentMethodsPage({super.key, required this.home});

  final HomeData home;

  @override
  State<PaymentMethodsPage> createState() => _PaymentMethodsPageState();
}

class _PaymentMethodsPageState extends State<PaymentMethodsPage> {
  final _api = ApiClient();
  List<_SavedMethod> _items = [];
  bool _loading = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!_live) {
      setState(() {
        _items = [
          const _SavedMethod(id: 1, type: 'card', label: 'Visa •••• 4242', details: {'card_brand': 'Visa', 'card_last4': '4242'}),
        ];
      });
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await _api.getJson('/app/account/payment-methods');
      if (!mounted) {
        return;
      }
      setState(() {
        _items = (payload['methods'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _SavedMethod.fromJson(Map<String, dynamic>.from(item)))
            .toList();
        _loading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _add(String type) async {
    final details = await Navigator.of(context).push<BookPaymentDetails>(
      MaterialPageRoute(
        builder: (_) => BookPaymentPage(method: type, home: widget.home, amountLabel: 'Save for later'),
      ),
    );
    if (details == null || !details.isComplete) {
      return;
    }
    final local = _SavedMethod(id: DateTime.now().millisecondsSinceEpoch, type: type, label: details.summary, details: details.toApi());
    setState(() => _items = [..._items, local]);
    if (!_live) {
      return;
    }
    try {
      await _api.postJson('/app/account/payment-methods', {
        'type': type,
        'is_default': _items.length == 1,
        'details': details.toApi(),
      });
      await _load();
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$error')));
      }
    }
  }

  Future<void> _remove(_SavedMethod item) async {
    setState(() => _items = _items.where((method) => method.id != item.id).toList());
    if (_live) {
      try {
        await _api.deleteJson('/app/account/payment-methods/${item.id}');
      } catch (_) {}
    }
  }

  @override
  Widget build(BuildContext context) {
    return ProfileScaffold(
      title: 'Payment Methods',
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
        children: [
          const Text('Cards, wallets, and PayPal saved to your Lens account.', style: TextStyle(color: Color(0xFF8E8B84))),
          const SizedBox(height: 16),
          if (_loading) const Center(child: CircularProgressIndicator(color: LensColors.primary)),
          for (final item in _items) _tile(item),
          const SizedBox(height: 8),
          _addButton(Icons.credit_card_outlined, 'Add bank card', 'card'),
          _addButton(Icons.phone_iphone_rounded, 'Add mobile wallet', 'wallet'),
          _addButton(Icons.payments_outlined, 'Add PayPal', 'paypal'),
        ],
      ),
    );
  }

  Widget _tile(_SavedMethod item) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(16),
        child: ListTile(
          leading: Icon(item.icon, color: LensColors.primary),
          title: Text(item.label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
          subtitle: Text(item.typeLabel, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
          trailing: IconButton(
            onPressed: () => _remove(item),
            icon: const Icon(Icons.delete_outline_rounded, color: Color(0xFFFF4D4F)),
          ),
        ),
      ),
    );
  }

  Widget _addButton(IconData icon, String label, String type) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: OutlinedButton.icon(
        onPressed: () => _add(type),
        icon: Icon(icon, size: 18),
        label: Text(label, style: const TextStyle(fontWeight: FontWeight.w800)),
        style: OutlinedButton.styleFrom(
          foregroundColor: Colors.white,
          side: const BorderSide(color: Color(0xFF3A3A3E)),
          minimumSize: const Size.fromHeight(50),
          shape: const StadiumBorder(),
        ),
      ),
    );
  }
}

class _SavedMethod {
  const _SavedMethod({required this.id, required this.type, required this.label, this.details = const {}});

  factory _SavedMethod.fromJson(Map<String, dynamic> json) {
    return _SavedMethod(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      type: json['type']?.toString() ?? 'card',
      label: json['label']?.toString() ?? 'Saved method',
      details: Map<String, dynamic>.from(json['details'] as Map? ?? const {}),
    );
  }

  final int id;
  final String type;
  final String label;
  final Map<String, dynamic> details;

  String get typeLabel => switch (type) {
        'wallet' => 'Mobile wallet',
        'paypal' => 'PayPal',
        _ => 'Bank card',
      };

  IconData get icon => switch (type) {
        'wallet' => Icons.phone_iphone_rounded,
        'paypal' => Icons.payments_outlined,
        _ => Icons.credit_card_outlined,
      };
}
