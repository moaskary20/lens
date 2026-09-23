import 'package:flutter/material.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/theme/lens_colors.dart';

class NotificationsPage extends StatefulWidget {
  const NotificationsPage({super.key, required this.home});

  final HomeData home;

  @override
  State<NotificationsPage> createState() => _NotificationsPageState();
}

class _NotificationsPageState extends State<NotificationsPage> {
  @override
  void initState() {
    super.initState();
    NotificationsStore.instance.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
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
                    onPressed: () => NotificationsStore.instance.markAllRead(),
                    child: const Text('Mark all read', style: TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700)),
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListenableBuilder(
                listenable: NotificationsStore.instance,
                builder: (context, _) {
                  final items = NotificationsStore.instance.items;
                  if (items.isEmpty) {
                    return const Center(
                      child: Padding(
                        padding: EdgeInsets.symmetric(horizontal: 36),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.notifications_none_rounded, color: LensColors.primary, size: 42),
                            SizedBox(height: 14),
                            Text('You are up to date', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                            SizedBox(height: 8),
                            Text(
                              'Booking, payment, and offer alerts from Lens will land here.',
                              textAlign: TextAlign.center,
                              style: TextStyle(color: LensColors.slate, height: 1.4),
                            ),
                          ],
                        ),
                      ),
                    );
                  }
                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
                    itemCount: items.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) => _NoticeCard(notice: items[index]),
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
