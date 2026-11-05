import 'dart:async';

import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/shell/app_shell.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key, required this.home});

  final HomeData home;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  bool _booting = true;
  String? _bootError;

  @override
  void initState() {
    super.initState();
    SessionStore.instance.addListener(_onSession);
    unawaited(_boot());
  }

  @override
  void dispose() {
    SessionStore.instance.removeListener(_onSession);
    super.dispose();
  }

  void _onSession() {
    if (!mounted) {
      return;
    }
    if (SessionStore.instance.isGuest) {
      setState(() {
        _booting = false;
        _bootError = null;
      });
      return;
    }
    unawaited(_boot());
  }

  Future<void> _boot() async {
    if (SessionStore.instance.isGuest) {
      if (mounted) {
        setState(() {
          _booting = false;
          _bootError = null;
        });
      }
      return;
    }

    setState(() {
      _booting = NotificationsStore.instance.items.isEmpty;
      _bootError = null;
    });

    try {
      await NotificationsStore.instance.openAndClear().timeout(const Duration(seconds: 12));
      if (!mounted) {
        return;
      }
      setState(() {
        _booting = false;
        _bootError = NotificationsStore.instance.items.isEmpty ? NotificationsStore.instance.error : null;
      });
    } on TimeoutException {
      if (!mounted) {
        return;
      }
      setState(() {
        _booting = false;
        _bootError = NotificationsStore.instance.items.isEmpty
            ? 'Loading took too long. Pull to retry.'
            : null;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _booting = false;
        _bootError = NotificationsStore.instance.items.isEmpty
            ? error.toString().replaceFirst('ApiException: ', '')
            : null;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (SessionStore.instance.isGuest) {
      return Scaffold(
        backgroundColor: LensColors.charcoal,
        body: Stack(
          children: [
            LoginView(bootstrap: widget.home.registerCatalog),
            SafeArea(
              child: IconButton(
                tooltip: 'Back',
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.chevron_left_rounded, color: Colors.white, size: 30),
              ),
            ),
          ],
        ),
      );
    }

    return Scaffold(
      backgroundColor: LensColors.charcoal,
      bottomNavigationBar: AppShell.navBar(context, index: 0),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 6, 12, 8),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
                  ),
                  const Expanded(
                    child: Text('Notifications', style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800)),
                  ),
                  TextButton(
                    onPressed: () => NotificationsStore.instance.clearAll(),
                    child: const Text('Clear message', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListenableBuilder(
                listenable: NotificationsStore.instance,
                builder: (context, _) {
                  final items = NotificationsStore.instance.items;
                  final storeError = NotificationsStore.instance.error;
                  final error = _bootError ?? (items.isEmpty ? storeError : null);

                  if (_booting && items.isEmpty) {
                    return const Center(
                      child: SizedBox(
                        width: 28,
                        height: 28,
                        child: CircularProgressIndicator(strokeWidth: 2.4, color: LensColors.primary),
                      ),
                    );
                  }

                  if (error != null && items.isEmpty) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 36),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.wifi_off_rounded, color: LensColors.primary, size: 42),
                            const SizedBox(height: 14),
                            const Text(
                              'Could not load notifications',
                              style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              error,
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: LensColors.slate, height: 1.4),
                            ),
                            const SizedBox(height: 18),
                            TextButton(
                              onPressed: () => unawaited(_boot()),
                              child: const Text('Try again', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  if (items.isEmpty) {
                    return RefreshIndicator(
                      color: LensColors.primary,
                      backgroundColor: const Color(0xFF1A1B1F),
                      onRefresh: _boot,
                      child: ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: const [
                          SizedBox(height: 120),
                          Icon(Icons.notifications_none_rounded, color: LensColors.primary, size: 42),
                          SizedBox(height: 14),
                          Text(
                            'You are up to date',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                          ),
                          SizedBox(height: 8),
                          Padding(
                            padding: EdgeInsets.symmetric(horizontal: 36),
                            child: Text(
                              'Booking, payment, and offer alerts from Lens will land here.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: LensColors.slate, height: 1.4),
                            ),
                          ),
                        ],
                      ),
                    );
                  }

                  return RefreshIndicator(
                    color: LensColors.primary,
                    backgroundColor: const Color(0xFF1A1B1F),
                    onRefresh: _boot,
                    child: ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const SizedBox(height: 10),
                      itemBuilder: (context, index) => _NoticeCard(notice: items[index]),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NoticeCard extends StatelessWidget {
  const _NoticeCard({required this.notice});

  final AppNotice notice;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: notice.read ? const Color(0xFF141518) : const Color(0xFF1A120C),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: () => NotificationsStore.instance.markRead(notice),
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: const Color(0xFF24140E),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0x55FF5A1F)),
                ),
                child: Icon(_iconFor(notice.event), color: LensColors.primary, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(notice.title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 15)),
                        ),
                        if (!notice.read)
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(color: LensColors.primary, shape: BoxShape.circle),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(notice.body, style: const TextStyle(color: Color(0xFFD0CBC3), fontSize: 13, height: 1.35)),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  IconData _iconFor(String event) {
    return switch (event) {
      'booking_created' || 'booking_accepted' || 'booking_status' => Icons.calendar_today_outlined,
      'booking_cancelled' => Icons.event_busy_outlined,
      'payment' || 'payout_transfer' || 'commission' => Icons.account_balance_wallet_outlined,
      'offer' => Icons.local_offer_outlined,
      'review' => Icons.star_outline_rounded,
      'message' => Icons.chat_bubble_outline_rounded,
      _ => Icons.notifications_none_rounded,
    };
  }
}
