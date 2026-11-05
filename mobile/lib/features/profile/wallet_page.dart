import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

void openWallet(BuildContext context, HomeData home) {
  Navigator.of(context).push(
    MaterialPageRoute<void>(builder: (_) => WalletPage(home: home)),
  );
}

class WalletPage extends StatefulWidget {
  const WalletPage({super.key, required this.home});

  final HomeData home;

  @override
  State<WalletPage> createState() => _WalletPageState();
}

class _WalletPageState extends State<WalletPage> {
  final _api = ApiClient();
  var _loading = true;
  String? _error;
  String _currency = 'EGP';
  String _role = 'client';
  _WalletBalances _balances = const _WalletBalances();
  List<_WalletTx> _transactions = const [];

  bool get _signedIn => SessionStore.instance.account != null;
  bool get _live => LensConfig.useNetwork && _signedIn;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (!_signedIn) {
      setState(() {
        _loading = false;
        _error = null;
      });
      return;
    }
    if (!_live) {
      setState(() {
        _loading = false;
        _currency = 'EGP';
        _role = SessionStore.instance.isVendor ? 'vendor' : 'client';
        _balances = SessionStore.instance.isVendor
            ? const _WalletBalances(
                available: 4200,
                pending: 1800,
                couponCredit: 0,
                spendable: 4200,
                lifetimeEarned: 12800,
                lifetimeWithdrawn: 6400,
                lifetimeCommission: 1600,
              )
            : const _WalletBalances(
                available: 350,
                pending: 0,
                couponCredit: 200,
                spendable: 550,
                lifetimePaid: 4800,
                lifetimeRefunded: 0,
              );
        _transactions = [
          _WalletTx(
            id: 1,
            label: SessionStore.instance.isVendor ? 'Earnings available' : 'Booking payment',
            signedAmount: SessionStore.instance.isVendor ? 1440 : -1980,
            createdLabel: '2 Nov 2026 · 4:20 PM',
            bookingReference: 'LN-2041',
            type: SessionStore.instance.isVendor ? 'earning_release' : 'payment',
          ),
          _WalletTx(
            id: 2,
            label: SessionStore.instance.isVendor ? 'Earnings held' : 'Coupon credit',
            signedAmount: SessionStore.instance.isVendor ? 1440 : 200,
            createdLabel: '28 Oct 2026 · 11:05 AM',
            bookingReference: 'LN-2033',
            type: SessionStore.instance.isVendor ? 'earning_hold' : 'coupon',
          ),
        ];
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final payload = await _api.getJson('/app/account/wallet');
      if (!mounted) {
        return;
      }
      final wallet = Map<String, dynamic>.from((payload['wallet'] as Map?) ?? const {});
      setState(() {
        _currency = payload['currency']?.toString() ?? 'EGP';
        _role = payload['role']?.toString() ?? _role;
        _balances = _WalletBalances.fromJson(wallet);
        _transactions = (payload['transactions'] as List<dynamic>? ?? const [])
            .whereType<Map>()
            .map((item) => _WalletTx.fromJson(Map<String, dynamic>.from(item)))
            .toList();
        _loading = false;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _loading = false;
        _error = '$error';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_signedIn) {
      return ProfileScaffold(
        title: 'Wallet',
        child: LoginView(
          bootstrap: widget.home.registerCatalog,
          onSuccess: () {
            if (mounted) {
              setState(() {});
              _load();
            }
          },
        ),
      );
    }

    return ProfileScaffold(
      title: 'Wallet',
      action: IconButton(
        tooltip: 'Refresh',
        onPressed: _loading ? null : _load,
        icon: const Icon(Icons.refresh_rounded, color: LensColors.cream),
      ),
      child: _loading
          ? const Center(child: CircularProgressIndicator(color: LensColors.primary))
          : RefreshIndicator(
              color: LensColors.primary,
              backgroundColor: const Color(0xFF141518),
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 28),
                children: [
                  if (_error != null) ...[
                    Text(_error!, style: const TextStyle(color: Color(0xFFFF8A7A))),
                    const SizedBox(height: 12),
                  ],
                  _BalanceHero(
                    currency: _currency,
                    balances: _balances,
                    vendor: _role == 'vendor',
                  ),
                  const SizedBox(height: 16),
                  _StatGrid(
                    currency: _currency,
                    balances: _balances,
                    vendor: _role == 'vendor',
                  ),
                  const SizedBox(height: 22),
                  const Text(
                    'Activity',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 10),
                  if (_transactions.isEmpty)
                    const _EmptyActivity()
                  else
                    for (final tx in _transactions) ...[
                      _TxTile(tx: tx, currency: _currency),
                      const SizedBox(height: 8),
                    ],
                ],
              ),
            ),
    );
  }
}

class _WalletBalances {
  const _WalletBalances({
    this.available = 0,
    this.pending = 0,
    this.couponCredit = 0,
    this.spendable = 0,
    this.lifetimeEarned = 0,
    this.lifetimeWithdrawn = 0,
    this.lifetimeCommission = 0,
    this.lifetimePaid = 0,
    this.lifetimeRefunded = 0,
  });

  factory _WalletBalances.fromJson(Map<String, dynamic> json) {
    double n(Object? value) => value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
    return _WalletBalances(
      available: n(json['available']),
      pending: n(json['pending']),
      couponCredit: n(json['coupon_credit']),
      spendable: n(json['spendable']),
      lifetimeEarned: n(json['lifetime_earned']),
      lifetimeWithdrawn: n(json['lifetime_withdrawn']),
      lifetimeCommission: n(json['lifetime_commission']),
      lifetimePaid: n(json['lifetime_paid']),
      lifetimeRefunded: n(json['lifetime_refunded']),
    );
  }

  final double available;
  final double pending;
  final double couponCredit;
  final double spendable;
  final double lifetimeEarned;
  final double lifetimeWithdrawn;
  final double lifetimeCommission;
  final double lifetimePaid;
  final double lifetimeRefunded;
}

class _WalletTx {
  const _WalletTx({
    required this.id,
    required this.label,
    required this.signedAmount,
    required this.createdLabel,
    required this.type,
    this.bookingReference,
  });

  factory _WalletTx.fromJson(Map<String, dynamic> json) {
    return _WalletTx(
      id: json['id'] is int ? json['id'] as int : int.tryParse('${json['id']}') ?? 0,
      label: json['label']?.toString() ?? 'Transaction',
      signedAmount: json['signed_amount'] is num
          ? (json['signed_amount'] as num).toDouble()
          : double.tryParse('${json['signed_amount']}') ?? 0,
      createdLabel: json['created_label']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      bookingReference: json['booking_reference']?.toString(),
    );
  }

  final int id;
  final String label;
  final double signedAmount;
  final String createdLabel;
  final String type;
  final String? bookingReference;
}

class _BalanceHero extends StatelessWidget {
  const _BalanceHero({
    required this.currency,
    required this.balances,
    required this.vendor,
  });

  final String currency;
  final _WalletBalances balances;
  final bool vendor;

  @override
  Widget build(BuildContext context) {
    final headline = vendor ? balances.available : balances.spendable;
    final caption = vendor ? 'Available to withdraw' : 'Ready to spend';

    return Container(
      key: const Key('wallet-balance-hero'),
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF2A160E),
            Color(0xFF141518),
            Color(0xFF1A1B1F),
          ],
        ),
        border: Border.all(color: const Color(0xFF3A2A22)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: LensColors.primary.withValues(alpha: 0.16),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(
                  Icons.account_balance_wallet_rounded,
                  color: LensColors.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  vendor ? 'Creator wallet' : 'Lens wallet',
                  style: const TextStyle(
                    color: Color(0xFFC8C3BA),
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          Text(
            caption,
            style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13),
          ),
          const SizedBox(height: 6),
          Text(
            '$currency ${_money(headline)}',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 34,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.6,
            ),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              _Pill(
                label: 'Available',
                value: '$currency ${_money(balances.available)}',
              ),
              _Pill(
                label: 'Held',
                value: '$currency ${_money(balances.pending)}',
              ),
              if (!vendor || balances.couponCredit > 0)
                _Pill(
                  label: 'Coupons',
                  value: '$currency ${_money(balances.couponCredit)}',
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StatGrid extends StatelessWidget {
  const _StatGrid({
    required this.currency,
    required this.balances,
    required this.vendor,
  });

  final String currency;
  final _WalletBalances balances;
  final bool vendor;

  @override
  Widget build(BuildContext context) {
    final items = vendor
        ? [
            ('Earned', balances.lifetimeEarned),
            ('Withdrawn', balances.lifetimeWithdrawn),
            ('Commission', balances.lifetimeCommission),
            ('Pending', balances.pending),
          ]
        : [
            ('Paid', balances.lifetimePaid),
            ('Refunded', balances.lifetimeRefunded),
            ('Coupons', balances.couponCredit),
            ('Spendable', balances.spendable),
          ];

    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      mainAxisSpacing: 8,
      crossAxisSpacing: 8,
      childAspectRatio: 1.7,
      children: [
        for (final item in items)
          Container(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
            decoration: BoxDecoration(
              color: const Color(0xFF141518),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFF22242A)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  item.$1,
                  style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                ),
                const SizedBox(height: 6),
                Text(
                  '$currency ${_money(item.$2)}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

class _TxTile extends StatelessWidget {
  const _TxTile({required this.tx, required this.currency});

  final _WalletTx tx;
  final String currency;

  @override
  Widget build(BuildContext context) {
    final credit = tx.signedAmount >= 0;
    final amountColor = credit ? LensColors.success : const Color(0xFFFF8A7A);
    final sign = credit ? '+' : '';

    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF22242A)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFF1C1E24),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              credit ? Icons.south_west_rounded : Icons.north_east_rounded,
              color: amountColor,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tx.label,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 3),
                Text(
                  [
                    if ((tx.bookingReference ?? '').isNotEmpty) tx.bookingReference!,
                    if (tx.createdLabel.isNotEmpty) tx.createdLabel,
                  ].join(' · '),
                  style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
                ),
              ],
            ),
          ),
          Text(
            '$sign$currency ${_money(tx.signedAmount.abs())}',
            style: TextStyle(
              color: amountColor,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyActivity extends StatelessWidget {
  const _EmptyActivity();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(18, 28, 18, 28),
      decoration: BoxDecoration(
        color: const Color(0xFF141518),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF22242A)),
      ),
      child: const Column(
        children: [
          Icon(Icons.receipt_long_outlined, color: Color(0xFF8E8B84), size: 34),
          SizedBox(height: 10),
          Text(
            'No wallet activity yet',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
          ),
          SizedBox(height: 6),
          Text(
            'Payments, earnings, coupons, and refunds will show up here.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Color(0xFF8E8B84), height: 1.35),
          ),
        ],
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1E24),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFF2A2D34)),
      ),
      child: Text.rich(
        TextSpan(
          children: [
            TextSpan(
              text: '$label  ',
              style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12),
            ),
            TextSpan(
              text: value,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

String _money(double value) {
  final fixed = value == value.roundToDouble()
      ? value.toStringAsFixed(0)
      : value.toStringAsFixed(2);
  final parts = fixed.split('.');
  final whole = parts.first.replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (match) => ',',
  );
  return parts.length == 1 ? whole : '$whole.${parts[1]}';
}
