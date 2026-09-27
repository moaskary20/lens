import 'package:flutter/material.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/auth/role_choice_page.dart';
import 'package:lens/features/auth/user_register_page.dart';
import 'package:lens/features/auth/vendor_register_page.dart';
import 'package:lens/features/home/inbox.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';
import 'package:lens/features/profile/addresses_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';
import 'package:lens/features/profile/edit_profile_page.dart';
import 'package:lens/features/profile/messages_page.dart';
import 'package:lens/features/profile/payment_methods_page.dart';
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

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListenableBuilder(
        listenable: SessionStore.instance,
        builder: (context, _) {
          final signedIn = SessionStore.instance.account != null;
          return ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 96),
            children: [
              _header(context),
              const SizedBox(height: 18),
              _identity(context),
              const SizedBox(height: 22),
              const _StatsRow(),
              const SizedBox(height: 16),
              const _LanguagePicker(),
              const SizedBox(height: 18),
              _row(context, Icons.calendar_today_outlined, 'My Bookings', onTap: () => _guard(context, () {
                if (onOpenBookings != null) {
                  onOpenBookings!();
                  return;
                }
                _open(context, 'My Bookings');
              })),
              _row(context, Icons.favorite_border_rounded, 'Saved Creators', onTap: () => openFavorites(context, data)),
              _row(context, Icons.credit_card_outlined, 'Payment Methods', onTap: () => _guard(context, () => _openPage(context, PaymentMethodsPage(home: data)))),
              _row(context, Icons.location_on_outlined, 'Addresses', onTap: () => _guard(context, () => _openPage(context, const AddressesPage()))),
              _row(context, Icons.notifications_none_rounded, 'Notifications', onTap: () => _guard(context, () => openNotifications(context, data))),
              _row(context, Icons.chat_bubble_outline_rounded, 'Messages', onTap: () => _guard(context, () => _openPage(context, MessagesPage(home: data)))),
              _row(context, Icons.settings_outlined, 'App Settings', onTap: () => _openPage(context, AppSettingsPage(home: data))),
              _row(context, Icons.description_outlined, 'Policies'),
              _row(context, Icons.help_outline_rounded, 'Report an Issue'),
              if (signedIn)
                _row(context, Icons.logout_rounded, 'Log Out', danger: true, onTap: () => _logOut(context))
              else
                _authButtons(context),
            ],
          );
        },
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
          onPressed: () => _openPage(context, AppSettingsPage(home: data)),
          icon: const Icon(Icons.settings_outlined, color: LensColors.cream),
        ),
        if (data.on('notifications'))
          IconButton(
            onPressed: () => _guard(context, () => openNotifications(context, data)),
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
            _avatar(account),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  _statusChip(account),
                  const SizedBox(height: 4),
                  Text(email, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                ],
              ),
            ),
            GestureDetector(
              onTap: () {
                if (account == null) {
                  _openLogin(context);
                  return;
                }
                _openPage(context, EditProfilePage(home: data));
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

  Widget _statusChip(SessionAccount? account) {
    final label = account == null ? 'Not signed in' : (account.isActive ? 'Active' : 'Inactive');
    final color = account == null
        ? const Color(0xFFF79646)
        : (account.isActive ? LensColors.success : const Color(0xFFFF4D4F));

    return Container(
      padding: const EdgeInsets.fromLTRB(8, 4, 10, 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: color.withValues(alpha: 0.45)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 7,
            height: 7,
            decoration: BoxDecoration(color: color, shape: BoxShape.circle),
          ),
          const SizedBox(width: 6),
          Text(label, style: TextStyle(color: color, fontSize: 11.5, fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }

  Widget _avatar(SessionAccount? account) {
    final url = account?.avatarUrl;
    return CircleAvatar(
      radius: 32,
      backgroundColor: LensColors.graphite,
      backgroundImage: url != null && url.isNotEmpty && LensConfig.useNetwork ? NetworkImage(url) : null,
      child: url != null && url.isNotEmpty && LensConfig.useNetwork
          ? null
          : Text(
              account?.initials ?? 'G',
              style: const TextStyle(color: LensColors.cream, fontWeight: FontWeight.w800, fontSize: 18),
            ),
    );
  }

  Widget _authButtons(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        children: [
          Expanded(child: _authButton(label: 'Log In', icon: Icons.login_rounded, onTap: () => _openLogin(context))),
          const SizedBox(width: 12),
          Expanded(child: _authButton(label: 'Register', icon: Icons.person_add_alt_1_rounded, onTap: () => _openRegister(context))),
        ],
      ),
    );
  }

  Widget _authButton({required String label, required IconData icon, required VoidCallback onTap}) {
    return SizedBox(
      height: 52,
      child: FilledButton.icon(
        onPressed: onTap,
        icon: Icon(icon, size: 18),
        label: Text(label, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
        style: FilledButton.styleFrom(
          backgroundColor: LensColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: const StadiumBorder(),
        ),
      ),
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

  Future<void> _guard(BuildContext context, VoidCallback action) async {
    if (SessionStore.instance.isGuest) {
      await _openLogin(context);
    }
    if (!context.mounted || SessionStore.instance.isGuest) {
      return;
    }
    action();
  }

  Future<void> _openLogin(BuildContext context) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (routeContext) => _AuthScreen(
          child: LoginView(
            bootstrap: bootstrap,
            onSuccess: () => Navigator.of(routeContext).pop(),
          ),
        ),
      ),
    );
  }

  Future<void> _openRegister(BuildContext context) async {
    final role = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(builder: (_) => const RoleChoicePage()),
    );
    if (!context.mounted || role == null) {
      return;
    }
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (routeContext) => role == 'vendor'
            ? VendorRegisterPage(bootstrap: bootstrap, onSuccess: () => Navigator.of(routeContext).pop())
            : UserRegisterPage(bootstrap: bootstrap, onSuccess: () => Navigator.of(routeContext).pop()),
      ),
    );
  }

  void _open(BuildContext context, String title) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => PlaceholderPage(title: title)));
  }

  void _openPage(BuildContext context, Widget page) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => page));
  }

  void _logOut(BuildContext context) {
    SessionStore.instance.signOut();
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute<void>(builder: (_) => OnboardingPage(bootstrap: bootstrap)),
      (route) => false,
    );
  }
}

class _AuthScreen extends StatelessWidget {
  const _AuthScreen({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      body: Stack(
        children: [
          child,
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
}

class _LanguagePicker extends StatelessWidget {
  const _LanguagePicker();

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: SessionStore.instance,
      builder: (context, _) {
        final locale = SessionStore.instance.locale;
        return Container(
          padding: const EdgeInsets.fromLTRB(16, 14, 14, 14),
          decoration: BoxDecoration(
            color: const Color(0xFF161412),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Row(
            children: [
              const Icon(Icons.translate_rounded, color: LensColors.primary),
              const SizedBox(width: 12),
              const Expanded(
                child: Text('Language', style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w700)),
              ),
              _chip(id: 'en', label: 'English', selected: locale == 'en'),
              const SizedBox(width: 8),
              _chip(id: 'ar', label: 'Arabic', selected: locale == 'ar'),
            ],
          ),
        );
      },
    );
  }

  Widget _chip({required String id, required String label, required bool selected}) {
    return GestureDetector(
      onTap: () => SessionStore.instance.setLocale(id),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? LensColors.primary : const Color(0xFF24140E),
          borderRadius: BorderRadius.circular(99),
          border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E)),
        ),
        child: Text(
          label,
          style: TextStyle(color: selected ? Colors.white : const Color(0xFFD0CBC3), fontWeight: FontWeight.w800, fontSize: 12.5),
        ),
      ),
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
