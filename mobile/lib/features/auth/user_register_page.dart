import 'package:flutter/material.dart';
import 'package:lens/core/egypt_phone.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/features/auth/register_catalog.dart';
import 'package:lens/features/auth/register_scaffold.dart';

class UserRegisterPage extends StatefulWidget {
  const UserRegisterPage({super.key, required this.bootstrap, this.onSuccess});

  final Map<String, dynamic> bootstrap;
  final VoidCallback? onSuccess;

  @override
  State<UserRegisterPage> createState() => _UserRegisterPageState();
}

class _UserRegisterPageState extends State<UserRegisterPage> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  final _phone = TextEditingController();
  late final RegisterCatalog _catalog;
  int _step = 0;
  bool _busy = false;
  bool _hidePassword = true;
  String? _error;
  String? _cityId;
  String _locale = 'en';

  @override
  void initState() {
    super.initState();
    _catalog = RegisterCatalog.fromBootstrap(widget.bootstrap);
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    setState(() => _error = null);
    if (_step == 0) {
      if (_name.text.trim().isEmpty || _email.text.trim().isEmpty || _password.text.length < 6) {
        setState(() => _error = 'Enter your name, email, and a password of at least 6 characters.');
        return;
      }
      if (_password.text != _confirm.text) {
        setState(() => _error = 'Passwords do not match.');
        return;
      }
      setState(() => _step = 1);
      return;
    }
    if (!EgyptPhone.isValid(_phone.text, required: true)) {
      setState(() => _error = EgyptPhone.message);
      return;
    }
    setState(() => _busy = true);
    try {
      await SessionStore.instance.register(
        name: _name.text,
        email: _email.text,
        password: _password.text,
        role: 'client',
        extra: {
          'phone': EgyptPhone.digits(_phone.text),
          'locale': _locale,
          if (_cityId != null && int.tryParse(_cityId!) != null) 'city_id': int.parse(_cityId!),
        },
      );
      if (widget.onSuccess != null) {
        widget.onSuccess!();
      } else if (mounted) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      setState(() => _error = error.toString().replaceFirst('Exception: ', '').replaceFirst('ApiException: ', ''));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return RegisterScaffold(
      title: _step == 0 ? 'Create your account' : 'Your profile',
      subtitle: _step == 0
          ? 'The same client fields used in the Lens admin panel.'
          : 'Phone, governorate, and language appear on your user record.',
      step: _step,
      total: 2,
      busy: _busy,
      error: _error,
      onBack: () {
        if (_step == 0) {
          Navigator.of(context).maybePop();
          return;
        }
        setState(() => _step = 0);
      },
      onContinue: _continue,
      child: _step == 0 ? _account() : _profile(),
    );
  }

  Widget _account() {
    return Column(
      children: [
        RegisterField(controller: _name, hint: 'Full name', icon: Icons.person_outline_rounded),
        const SizedBox(height: 12),
        RegisterField(controller: _email, hint: 'Email', icon: Icons.mail_outline_rounded, keyboard: TextInputType.emailAddress),
        const SizedBox(height: 12),
        RegisterField(
          controller: _password,
          hint: 'Password',
          icon: Icons.lock_outline_rounded,
          secret: _hidePassword,
          suffix: IconButton(
            onPressed: () => setState(() => _hidePassword = !_hidePassword),
            icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, color: const Color(0xFF8E8B84)),
          ),
        ),
        const SizedBox(height: 12),
        RegisterField(controller: _confirm, hint: 'Confirm password', icon: Icons.lock_outline_rounded, secret: _hidePassword),
      ],
    );
  }

  Widget _profile() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        RegisterField(
          controller: _phone,
          hint: 'Phone 010 / 011 / 012 / 015',
          icon: Icons.phone_outlined,
          keyboard: TextInputType.phone,
          digitsOnly: true,
          maxLength: 11,
        ),
        const SizedBox(height: 12),
        RegisterDropdown(
          value: _cityId,
          options: _catalog.cityChoices,
          hint: 'Governorate',
          onChanged: (value) => setState(() => _cityId = value),
        ),
        const SizedBox(height: 16),
        const Text('Language', style: TextStyle(color: Color(0xFFB0ABA3), fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        RegisterChoiceChips(
          options: const [
            CatalogOption(id: 'en', label: 'English'),
            CatalogOption(id: 'ar', label: 'Arabic'),
          ],
          selected: _locale,
          onSelect: (value) => setState(() => _locale = value),
        ),
      ],
    );
  }
}
