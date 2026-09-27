import 'package:flutter/material.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class AppSettingsPage extends StatefulWidget {
  const AppSettingsPage({super.key, required this.home});

  final HomeData home;

  @override
  State<AppSettingsPage> createState() => _AppSettingsPageState();
}

class _AppSettingsPageState extends State<AppSettingsPage> {
  static const _version = '1.0.0';
  final _api = ApiClient();
  late Map<String, dynamic> _settings;
  bool _saving = false;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.isClient;
  HomeData get home => widget.home;

  @override
  void initState() {
    super.initState();
    _settings = {
      'push_notifications': home.on('notifications'),
      'chat_alerts': home.on('chat'),
      'booking_reminders': home.on('bookings'),
      'email_offers': home.on('coupons'),
      'message_preview': true,
      'vibration': true,
      'review_prompts': home.on('reviews'),
      'read_receipts': home.on('chat'),
      'hide_activity': false,
      'haptic_feedback': true,
      'reduce_motion': false,
      'language': 'en',
    };
    _load();
  }

  Future<void> _load() async {
    if (!_live) {
      return;
    }
    try {
      final payload = await _api.getJson('/app/account/settings');
      if (!mounted) {
        return;
      }
      final remote = payload['settings'] is Map ? Map<String, dynamic>.from(payload['settings'] as Map) : const <String, dynamic>{};
      setState(() => _settings = {..._settings, ...remote});
    } catch (_) {}
  }

  Future<void> _set(String key, Object value) async {
    setState(() {
      _settings[key] = value;
      _saving = true;
    });
    if (_live) {
      try {
        await _api.postJson('/app/account/settings', {'settings': _settings});
      } catch (_) {}
    }
    if (mounted) {
      setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ProfileScaffold(
      title: 'App Settings',
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 32),
        children: [
          _hero(),
          if (_saving) const Padding(padding: EdgeInsets.only(bottom: 12), child: LinearProgressIndicator(color: LensColors.primary, minHeight: 2)),
          _group('Alerts', [
            if (home.on('notifications')) _toggle(Icons.notifications_active_outlined, 'push_notifications', 'Push notifications', 'Bookings, messages, and account alerts'),
            if (home.on('chat')) _toggle(Icons.chat_bubble_outline_rounded, 'chat_alerts', 'Chat alerts', 'Notify me when a creator replies'),
            if (home.on('chat')) _toggle(Icons.visibility_outlined, 'message_preview', 'Message preview', 'Show the first line on the lock screen'),
            if (home.on('bookings')) _toggle(Icons.event_available_outlined, 'booking_reminders', 'Booking reminders', 'Remind me before a session starts'),
            if (home.on('reviews')) _toggle(Icons.star_outline_rounded, 'review_prompts', 'Review prompts', 'Ask me to rate after a session is approved'),
            if (home.on('coupons')) _toggle(Icons.local_offer_outlined, 'email_offers', 'Offers & deals', 'Seasonal codes and first-order emails'),
            _toggle(Icons.vibration_rounded, 'vibration', 'Vibration', 'Vibrate with alerts on this device'),
          ]),
          _group('Privacy', [
            if (home.on('chat')) _toggle(Icons.done_all_rounded, 'read_receipts', 'Read receipts', 'Let creators see when you opened a chat'),
            _toggle(Icons.visibility_off_outlined, 'hide_activity', 'Hide activity', 'Do not show last seen on creator chats'),
          ]),
          _group('Display & feel', [
            _toggle(Icons.touch_app_outlined, 'haptic_feedback', 'Haptic feedback', 'Light tap on buttons and switches'),
            _toggle(Icons.motion_photos_off_outlined, 'reduce_motion', 'Reduce motion', 'Limit animations across Lens'),
          ]),
          _about(),
        ],
      ),
    );
  }

  Widget _hero() {
    return Container(
      margin: const EdgeInsets.only(bottom: 22),
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF24140E), Color(0xFF161412)],
        ),
        border: Border.all(color: const Color(0x55FF5A1F)),
      ),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(color: LensColors.primary, borderRadius: BorderRadius.circular(16)),
            child: const Icon(Icons.camera_alt_rounded, color: Colors.white),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Lens', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                Text(home.tagline.toUpperCase(), style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 11, letterSpacing: 1.1, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _group(String title, List<Widget> children) {
    if (children.isEmpty) {
      return const SizedBox.shrink();
    }
    return Padding(
      padding: const EdgeInsets.only(bottom: 22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 17)),
          const SizedBox(height: 10),
          DecoratedBox(
            decoration: BoxDecoration(color: const Color(0xFF161412), borderRadius: BorderRadius.circular(18)),
            child: Column(children: [
              for (var i = 0; i < children.length; i++) ...[
                children[i],
                if (i != children.length - 1) const Divider(height: 1, color: Color(0xFF2A2A2E)),
              ],
            ]),
          ),
        ],
      ),
    );
  }

  Widget _toggle(IconData icon, String key, String title, String hint) {
    return SwitchListTile(
      value: _settings[key] == true,
      onChanged: (value) => _set(key, value),
      secondary: Icon(icon, color: LensColors.primary),
      title: Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15)),
      subtitle: Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
      activeThumbColor: Colors.white,
      activeTrackColor: LensColors.primary,
    );
  }

  Widget _about() {
    return _group('About App', [
      _aboutRow(Icons.info_outline_rounded, 'Lens $_version', 'Marketplace for photographers, studios, and clients in Egypt.'),
      _link(Icons.auto_awesome_outlined, 'What is Lens?', home.tagline, 'Lens helps you find, book, and chat with verified creators. Payments stay in escrow until you approve the work.'),
      if (home.on('cms')) _link(Icons.description_outlined, 'Terms of Service', 'Booking, payments, and protected delivery', 'Terms for using Lens for booking, payments, and protected delivery.'),
      if (home.on('cms')) _link(Icons.privacy_tip_outlined, 'Privacy Policy', 'How we store client and vendor data', 'Lens stores account, booking, and chat data to run sessions. Full card numbers are never saved.'),
      _link(Icons.support_agent_outlined, 'Contact support', 'help@lens.app', 'Email help@lens.app or open Report an Issue from your profile.'),
    ]);
  }

  Widget _aboutRow(IconData icon, String title, String hint) {
    return ListTile(
      leading: Icon(icon, color: LensColors.primary),
      title: Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
      subtitle: Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
    );
  }

  Widget _link(IconData icon, String title, String hint, String body) {
    return ListTile(
      leading: Icon(icon, color: LensColors.primary),
      title: Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
      subtitle: Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
      trailing: const Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
      onTap: () => _sheet(title, body),
    );
  }

  void _sheet(String title, String body) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(22, 20, 22, 32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 10),
              Text(body, style: const TextStyle(color: Color(0xFFD0CBC3), height: 1.45)),
            ],
          ),
        );
      },
    );
  }
}
