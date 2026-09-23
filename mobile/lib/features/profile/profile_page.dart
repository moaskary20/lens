import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';
import 'package:lens/features/shell/placeholder_page.dart';

class ProfilePage extends StatelessWidget {
  const ProfilePage({
    super.key,
    required this.data,
    required this.bootstrap,
    this.onOpenBookings,
  });

  final HomeData data;
  final Map<String, dynamic> bootstrap;
  final VoidCallback? onOpenBookings;

  static const _photo = 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=400&h=400&q=80';

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 96),
        children: [
          _header(context),
          const SizedBox(height: 18),
          _identity(context),
          const SizedBox(height: 22),
          const _StatsRow(),
          const SizedBox(height: 18),
          _row(context, Icons.calendar_today_outlined, 'My Bookings', onTap: () {
            if (onOpenBookings != null) {
              onOpenBookings!();
              return;
            }
            _open(context, 'My Bookings');
          }),
          _row(context, Icons.favorite_border_rounded, 'Saved Creators', onTap: () => openFavorites(context, data)),
          _row(context, Icons.credit_card_outlined, 'Payment Methods'),
          _row(context, Icons.location_on_outlined, 'Addresses'),
          _row(context, Icons.notifications_none_rounded, 'Notifications', onTap: () => openNotifications(context, data)),
          _row(context, Icons.chat_bubble_outline_rounded, 'Messages'),
          _row(context, Icons.settings_outlined, 'App Settings'),
          _row(context, Icons.description_outlined, 'Policies'),
          _row(context, Icons.help_outline_rounded, 'Report an Issue'),
          _row(context, Icons.logout_rounded, 'Log Out', danger: true, onTap: () => _logOut(context)),
        ],
      ),
    );
  }

  Widget _header(BuildContext context) {
    return Row(
      children: [
        const Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Lens.',
                style: TextStyle(color: LensColors.primary, fontSize: 32, fontWeight: FontWeight.w800, height: 1),
              ),
              SizedBox(height: 4),
              Text(
                'FIND. BOOK. CREATE.',
                style: TextStyle(color: LensColors.primary, fontSize: 9, letterSpacing: 1.5, fontWeight: FontWeight.w800),
              ),
            ],
          ),
        ),
        IconButton(
          onPressed: () => _open(context, 'App Settings'),
          icon: const Icon(Icons.settings_outlined, color: LensColors.cream),
        ),
        if (data.on('notifications'))
          IconButton(
            onPressed: () => openNotifications(context, data),
            icon: const Icon(Icons.notifications_none_rounded, color: LensColors.cream),
          ),
      ],
    );
  }

  Widget _identity(BuildContext context) {
    return ListenableBuilder(
      listenable: SessionStore.instance,
      builder: (context, _) {
        final account = SessionStore.instance.account;
        final name = account?.name ?? 'Guest';
        final email = account?.email ?? 'Sign in to manage bookings';
        return Row(
          children: [
            ClipOval(
              child: SizedBox(
                width: 64,
                height: 64,
                child: LensConfig.useNetwork
                    ? Image.network(
                        _photo,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const ColoredBox(
                          color: LensColors.graphite,
                          child: Icon(Icons.person, color: LensColors.cream),
                        ),
                      )
                    : const ColoredBox(
                        color: LensColors.graphite,
                        child: Icon(Icons.person, color: LensColors.cream),
                      ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 3),
                  Text(email, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                ],
              ),
            ),
            GestureDetector(
              onTap: () {
                if (account == null && onOpenBookings != null) {
                  onOpenBookings!();
                  return;
                }
                _open(context, 'Edit Profile');
              },
              child: Row(
                children: [
                  Text(
                    account == null ? 'Sign in' : 'Edit Profile',
                    style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w700, fontSize: 13),
                  ),
                  const SizedBox(width: 2),
                  const Icon(Icons.chevron_right_rounded, color: LensColors.primary, size: 18),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _row(BuildContext context, IconData icon, String title, {bool danger = false, VoidCallback? onTap}) {
    final color = danger ? const Color(0xFFFF4D4F) : Colors.white;
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap ?? () => _open(context, title),
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
            child: Row(
              children: [
                Icon(icon, color: color, size: 22),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(title, style: TextStyle(color: color, fontSize: 15, fontWeight: FontWeight.w700)),
                ),
                Icon(Icons.chevron_right_rounded, color: danger ? color : const Color(0xFF8E8B84), size: 22),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _open(BuildContext context, String title) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: title)));
  }

  void _logOut(BuildContext context) {
    SessionStore.instance.signOut();
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute<void>(builder: (_) => OnboardingPage(bootstrap: bootstrap)),
      (route) => false,
    );
  }
}

class _StatsRow extends StatelessWidget {
  const _StatsRow();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(horizontal: 8),
      child: Row(
        children: [
          _Stat(value: '12', label: 'Upcoming'),
          _Divider(),
          _Stat(value: '8', label: 'Completed'),
          _Divider(),
          _Stat(value: '3', label: 'Canceled'),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});

  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(value, style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
        ],
      ),
    );
  }
}

class _Divider extends StatelessWidget {
  const _Divider();

  @override
  Widget build(BuildContext context) {
    return Container(width: 1, height: 36, color: const Color(0xFF2A2A2E));
  }
}
