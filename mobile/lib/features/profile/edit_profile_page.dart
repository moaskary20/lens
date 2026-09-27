import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:lens/core/api/api_client.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/egypt_phone.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/register_catalog.dart';
import 'package:lens/features/auth/register_scaffold.dart';
import 'package:lens/features/profile/addresses_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';
import 'package:lens/features/profile/payment_methods_page.dart';
import 'package:lens/features/profile/profile_scaffold.dart';

class EditProfilePage extends StatefulWidget {
  const EditProfilePage({super.key, required this.home});

  final HomeData home;

  @override
  State<EditProfilePage> createState() => _EditProfilePageState();
}

class _EditProfilePageState extends State<EditProfilePage> {
  final _api = ApiClient();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _currentPassword = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();

  late final RegisterCatalog _catalog;
  XFile? _picked;
  String? _avatarUrl;
  String? _cityId;
  String _locale = 'en';
  String _roleLabel = 'Client';
  bool _active = true;
  String? _joinedAt;
  double _walletAvailable = 0;
  double _walletPending = 0;
  int _payments = 0;
  int _addresses = 0;
  int _bookings = 0;
  int _favorites = 0;
  bool _hidePassword = true;
  bool _saving = false;
  bool _loading = false;
  String? _error;

  bool get _live => LensConfig.useNetwork && SessionStore.instance.account != null;
  HomeData get home => widget.home;

  @override
  void initState() {
    super.initState();
    _catalog = RegisterCatalog.fromBootstrap(home.registerCatalog);
    _hydrate(SessionStore.instance.account);
    _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _currentPassword.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  void _hydrate(SessionAccount? account) {
    _name.text = account?.name ?? '';
    _email.text = account?.email ?? '';
    _phone.text = account?.phone ?? '';
    _avatarUrl = account?.avatarUrl;
    _cityId = account?.cityId?.toString();
    _locale = account?.locale ?? 'en';
    _roleLabel = account?.isVendor == true ? 'Vendor' : 'Client';
    _active = account?.isActive ?? true;
    _joinedAt = account?.joinedAt;
    if (!_live) {
      _payments = 1;
      _addresses = 1;
      _bookings = 12;
      _favorites = 8;
      _walletPending = 1800;
    }
  }

  Future<void> _load() async {
    if (!_live) {
      return;
    }
    setState(() => _loading = true);
    try {
      final payload = await _api.getJson('/app/account/profile');
      if (!mounted) {
        return;
      }
      setState(() {
        _name.text = payload['name']?.toString() ?? _name.text;
        _email.text = payload['email']?.toString() ?? _email.text;
        _phone.text = payload['phone']?.toString() ?? _phone.text;
        _avatarUrl = payload['avatar']?.toString();
        _cityId = payload['city_id']?.toString();
        _locale = payload['locale']?.toString() == 'ar' ? 'ar' : 'en';
        _roleLabel = payload['role_label']?.toString() ?? _roleLabel;
        _active = payload['is_active'] != false;
        _joinedAt = payload['joined_at']?.toString();
        _walletAvailable = (payload['wallet_available'] as num?)?.toDouble() ?? 0;
        _walletPending = (payload['wallet_pending'] as num?)?.toDouble() ?? 0;
        _payments = (payload['payment_methods_count'] as num?)?.toInt() ?? 0;
        _addresses = (payload['addresses_count'] as num?)?.toInt() ?? 0;
        _bookings = (payload['bookings_count'] as num?)?.toInt() ?? 0;
        _favorites = (payload['favorites_count'] as num?)?.toInt() ?? 0;
        _loading = false;
      });
    } catch (_) {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _save() async {
    setState(() => _error = null);
    if (_name.text.trim().isEmpty || _email.text.trim().isEmpty) {
      setState(() => _error = 'Name and email are required.');
      return;
    }
    if (!EgyptPhone.isValid(_phone.text, required: true)) {
      setState(() => _error = EgyptPhone.message);
      return;
    }
    if (_password.text.isNotEmpty) {
      if (_password.text.length < 6) {
        setState(() => _error = 'New password must be at least 6 characters.');
        return;
      }
      if (_password.text != _confirm.text) {
        setState(() => _error = 'New passwords do not match.');
        return;
      }
      if (_currentPassword.text.isEmpty) {
        setState(() => _error = 'Enter your current password to change it.');
        return;
      }
    }

    setState(() => _saving = true);
    final city = _catalog.cityChoices.where((item) => item.id == _cityId).firstOrNull;
    final next = (SessionStore.instance.account ??
            SessionAccount(name: _name.text.trim(), email: _email.text.trim(), role: 'client'))
        .copyWith(
      name: _name.text.trim(),
      email: _email.text.trim(),
      phone: EgyptPhone.digits(_phone.text),
      cityId: int.tryParse(_cityId ?? ''),
      cityName: city?.label,
      locale: _locale,
      avatarUrl: _avatarUrl,
    );

    try {
      if (_live) {
        final body = {
          'name': next.name,
          'email': next.email,
          'phone': next.phone,
          'locale': _locale,
          if (next.cityId != null) 'city_id': next.cityId,
          if (_password.text.isNotEmpty) 'password': _password.text,
          if (_password.text.isNotEmpty) 'current_password': _currentPassword.text,
        };
        final payload = _picked == null
            ? await _api.postJson('/app/account/profile', body)
            : await _api.postForm(
                '/app/account/profile',
                body,
                files: [await http.MultipartFile.fromPath('avatar', _picked!.path)],
              );
        SessionStore.instance.applyAccount(SessionAccount.fromJson(payload).copyWith(
          vendorId: next.vendorId,
          vendorName: next.vendorName,
        ));
      } else {
        SessionStore.instance.applyAccount(next);
      }
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Profile updated'), backgroundColor: Color(0xFF24140E)),
      );
      if (Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      setState(() => _error = error.toString().replaceFirst('ApiException: ', ''));
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  Future<void> _pickAvatar() async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 28),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Change photo', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 14),
              ListTile(
                leading: const Icon(Icons.photo_camera_outlined, color: LensColors.primary),
                title: const Text('Take a photo', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                onTap: () => Navigator.pop(context, ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined, color: LensColors.primary),
                title: const Text('Choose from gallery', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700)),
                onTap: () => Navigator.pop(context, ImageSource.gallery),
              ),
            ],
          ),
        );
      },
    );
    if (source == null) {
      return;
    }
    try {
      final picked = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: 900);
      if (picked == null || !mounted) {
        return;
      }
      setState(() => _picked = picked);
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open the photo picker on this device.');
      }
    }
  }

  Future<void> _pickCity() async {
    final query = TextEditingController();
    final selected = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF141416),
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setSheet) {
            final needle = query.text.trim().toLowerCase();
            final items = _catalog.cityChoices.where((item) => needle.isEmpty || item.label.toLowerCase().contains(needle)).toList();
            return Padding(
              padding: EdgeInsets.fromLTRB(20, 18, 20, 20 + MediaQuery.viewInsetsOf(context).bottom),
              child: SizedBox(
                height: MediaQuery.sizeOf(context).height * 0.7,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Governorate', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800)),
                    const SizedBox(height: 6),
                    const Text('Same 27 Egyptian governorates used on the admin user record.', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
                    const SizedBox(height: 14),
                    TextField(
                      controller: query,
                      onChanged: (_) => setSheet(() {}),
                      style: const TextStyle(color: Colors.white),
                      decoration: InputDecoration(
                        hintText: 'Search governorate',
                        hintStyle: const TextStyle(color: Color(0xFF8E8B84)),
                        prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF8E8B84)),
                        filled: true,
                        fillColor: const Color(0xFF1C1A18),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
                      ),
                    ),
                    const SizedBox(height: 10),
                    Expanded(
                      child: ListView.builder(
                        itemCount: items.length,
                        itemBuilder: (context, index) {
                          final item = items[index];
                          final on = item.id == _cityId;
                          return ListTile(
                            title: Text(item.label, style: TextStyle(color: on ? LensColors.primary : Colors.white, fontWeight: FontWeight.w700)),
                            trailing: on ? const Icon(Icons.check_rounded, color: LensColors.primary) : null,
                            onTap: () => Navigator.pop(context, item.id),
                          );
                        },
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
    query.dispose();
    if (selected != null) {
      setState(() => _cityId = selected);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ProfileScaffold(
      title: 'Edit Profile',
      action: TextButton(
        onPressed: _saving ? null : _save,
        child: Text(_saving ? 'Saving' : 'Save', style: const TextStyle(color: LensColors.primary, fontWeight: FontWeight.w800)),
      ),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
          if (_loading) const Padding(padding: EdgeInsets.only(bottom: 12), child: LinearProgressIndicator(color: LensColors.primary, minHeight: 2)),
          _hero(),
          const SizedBox(height: 16),
          _wallet(),
          const SizedBox(height: 22),
          _section('Personal details', 'Same fields as the admin Users profile.'),
          const SizedBox(height: 10),
          RegisterField(controller: _name, hint: 'Full name', icon: Icons.person_outline_rounded),
          const SizedBox(height: 12),
          RegisterField(controller: _email, hint: 'Email', icon: Icons.mail_outline_rounded, keyboard: TextInputType.emailAddress),
          const SizedBox(height: 12),
          RegisterField(
            controller: _phone,
            hint: 'Phone 010 / 011 / 012 / 015',
            icon: Icons.phone_outlined,
            keyboard: TextInputType.phone,
            digitsOnly: true,
            maxLength: 11,
          ),
          const SizedBox(height: 22),
          _section('Location & language', 'Governorate and language stored on your user record.'),
          const SizedBox(height: 10),
          _cityTile(),
          const SizedBox(height: 12),
          RegisterChoiceChips(
            options: const [
              CatalogOption(id: 'en', label: 'English'),
              CatalogOption(id: 'ar', label: 'Arabic'),
            ],
            selected: _locale,
            onSelect: (value) => setState(() => _locale = value),
          ),
          const SizedBox(height: 22),
          _section('Security', 'Leave blank to keep the current password.'),
          const SizedBox(height: 10),
          RegisterField(
            controller: _currentPassword,
            hint: 'Current password',
            icon: Icons.lock_outline_rounded,
            secret: _hidePassword,
            suffix: IconButton(
              onPressed: () => setState(() => _hidePassword = !_hidePassword),
              icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, color: const Color(0xFF8E8B84)),
            ),
          ),
          const SizedBox(height: 12),
          RegisterField(controller: _password, hint: 'New password', icon: Icons.lock_reset_rounded, secret: _hidePassword),
          const SizedBox(height: 12),
          RegisterField(controller: _confirm, hint: 'Confirm new password', icon: Icons.lock_outline_rounded, secret: _hidePassword),
          const SizedBox(height: 22),
          _section('Linked to this account', 'Also visible on the admin user page.'),
          const SizedBox(height: 10),
          _link(Icons.credit_card_outlined, 'Payment methods', '$_payments saved', () => _open(PaymentMethodsPage(home: home))),
          _link(Icons.location_on_outlined, 'Addresses', '$_addresses saved', () => _open(const AddressesPage())),
          _link(Icons.settings_outlined, 'App Settings', 'Alerts, privacy, and about', () => _open(AppSettingsPage(home: home))),
          if (_error != null) ...[
            const SizedBox(height: 14),
            Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
          ],
          const SizedBox(height: 18),
          SizedBox(
            height: 54,
            child: FilledButton(
              onPressed: _saving ? null : _save,
              style: FilledButton.styleFrom(
                backgroundColor: LensColors.primary,
                foregroundColor: Colors.white,
                disabledBackgroundColor: LensColors.primary.withValues(alpha: 0.5),
                elevation: 0,
                shape: const StadiumBorder(),
              ),
              child: _saving
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('Save changes', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
            ),
          ),
          ],
        ),
      ),
    );
  }

  Widget _hero() {
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 20, 18, 18),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF2A160E), Color(0xFF141210)],
        ),
        border: Border.all(color: const Color(0x55FF5A1F)),
      ),
      child: Column(
        children: [
          GestureDetector(
            onTap: _pickAvatar,
            child: Stack(
              children: [
                CircleAvatar(
                  radius: 46,
                  backgroundColor: const Color(0xFF24140E),
                  backgroundImage: _avatarImage(),
                  child: _avatarImage() == null
                      ? Text(_initials, style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800))
                      : null,
                ),
                Positioned(
                  right: 0,
                  bottom: 0,
                  child: Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      color: LensColors.primary,
                      shape: BoxShape.circle,
                      border: Border.all(color: const Color(0xFF2A160E), width: 3),
                    ),
                    child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 15),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          Text(_name.text.trim().isEmpty ? 'Your name' : _name.text.trim(), style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800)),
          const SizedBox(height: 4),
          Text(
            [_roleLabel, _cityLabel].where((item) => item != null && item.isNotEmpty).join(' · '),
            style: const TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            alignment: WrapAlignment.center,
            children: [
              _chip(_active ? 'Active account' : 'Inactive', _active ? LensColors.success : const Color(0xFFFF6B6B)),
              if (_joinedAt != null) _chip('Joined $_joinedAt', const Color(0xFF8E8B84)),
              _chip('$_bookings bookings', const Color(0xFF8E8B84)),
              _chip('$_favorites saved', const Color(0xFF8E8B84)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _wallet() {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      decoration: BoxDecoration(color: const Color(0xFF161412), borderRadius: BorderRadius.circular(18)),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(color: const Color(0xFF24140E), borderRadius: BorderRadius.circular(14)),
            child: const Icon(Icons.account_balance_wallet_outlined, color: LensColors.primary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Wallet', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                Text('Available EGP ${_walletAvailable.toStringAsFixed(0)} · Held EGP ${_walletPending.toStringAsFixed(0)}', style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _section(String title, String hint) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w800)),
        const SizedBox(height: 4),
        Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12.5)),
      ],
    );
  }

  Widget _cityTile() {
    return Material(
      color: const Color(0xE6121214),
      borderRadius: BorderRadius.circular(99),
      child: InkWell(
        onTap: _pickCity,
        borderRadius: BorderRadius.circular(99),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
          child: Row(
            children: [
              const Icon(Icons.map_outlined, color: Color(0xFF8E8B84)),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  _cityLabel ?? 'Governorate',
                  style: TextStyle(color: _cityLabel == null ? const Color(0xFF8E8B84) : Colors.white, fontSize: 15, fontWeight: FontWeight.w600),
                ),
              ),
              const Icon(Icons.expand_more_rounded, color: Color(0xFF8E8B84)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _link(IconData icon, String title, String hint, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: const Color(0xFF161412),
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                Icon(icon, color: LensColors.primary),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800)),
                      Text(hint, style: const TextStyle(color: Color(0xFF8E8B84), fontSize: 12)),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right_rounded, color: Color(0xFF8E8B84)),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _chip(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1410),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: color.withValues(alpha: 0.45)),
      ),
      child: Text(label, style: TextStyle(color: color, fontSize: 11.5, fontWeight: FontWeight.w800)),
    );
  }

  ImageProvider? _avatarImage() {
    if (_picked != null) {
      return FileImage(File(_picked!.path));
    }
    final url = _avatarUrl;
    if (url != null && url.isNotEmpty && LensConfig.useNetwork) {
      return NetworkImage(url);
    }
    return null;
  }

  String get _initials {
    final account = SessionStore.instance.account;
    if (account != null && _name.text.trim() == account.name) {
      return account.initials;
    }
    final parts = _name.text.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty).toList();
    if (parts.isEmpty) {
      return 'L';
    }
    return parts.take(2).map((part) => part.substring(0, 1).toUpperCase()).join();
  }

  String? get _cityLabel {
    for (final item in _catalog.cityChoices) {
      if (item.id == _cityId) {
        return item.label;
      }
    }
    return SessionStore.instance.account?.cityName;
  }

  void _open(Widget page) {
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => page));
  }
}
