import 'package:flutter/material.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';

class AuthPanel extends StatefulWidget {
  const AuthPanel({
    super.key,
    this.intendedRole = 'client',
    this.title = 'Sign in to continue',
    this.subtitle = 'Bookings are for registered clients and vendors.',
    this.startInRegister = false,
    this.onSuccess,
  });

  final String intendedRole;
  final String title;
  final String subtitle;
  final bool startInRegister;
  final VoidCallback? onSuccess;

  @override
  State<AuthPanel> createState() => _AuthPanelState();
}

class _AuthPanelState extends State<AuthPanel> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _register = false;
  late String _role;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _role = widget.intendedRole == 'vendor' ? 'vendor' : 'client';
    _register = widget.startInRegister;
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      if (_register) {
        await SessionStore.instance.register(
          name: _name.text,
          email: _email.text,
          password: _password.text,
          role: _role,
        );
      } else {
        await SessionStore.instance.login(email: _email.text, password: _password.text);
      }
      widget.onSuccess?.call();
    } catch (error) {
      setState(() => _error = error.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 28),
      children: [
        Text(widget.title, style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.w800, height: 1.15)),
        const SizedBox(height: 8),
        Text(widget.subtitle, style: const TextStyle(color: Color(0xFFB0ABA3), fontSize: 14, height: 1.4)),
        const SizedBox(height: 18),
        _modeSwitch(),
        if (_register) ...[
          const SizedBox(height: 14),
          _field(_name, 'Full name', Icons.person_outline_rounded),
          const SizedBox(height: 10),
          _roleSwitch(),
        ],
        const SizedBox(height: 10),
        _field(_email, 'Email', Icons.mail_outline_rounded, keyboard: TextInputType.emailAddress),
        const SizedBox(height: 10),
        _field(_password, 'Password', Icons.lock_outline_rounded, secret: true),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
        ],
        const SizedBox(height: 18),
        SizedBox(
          height: 52,
          child: FilledButton(
            onPressed: _busy ? null : _submit,
            style: FilledButton.styleFrom(
              backgroundColor: LensColors.primary,
              foregroundColor: Colors.white,
              shape: const StadiumBorder(),
            ),
            child: _busy
                ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : Text(_register ? 'Create account' : 'Sign in', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
          ),
        ),
        const SizedBox(height: 16),
        const Text(
          'Demo: client@lens.app or vendor@lens.app  ·  password',
          textAlign: TextAlign.center,
          style: TextStyle(color: LensColors.slate, fontSize: 12),
        ),
      ],
    );
  }

  Widget _modeSwitch() {
    return Row(
      children: [
        _chip('Sign in', !_register, () => setState(() => _register = false)),
        const SizedBox(width: 8),
        _chip('Create account', _register, () => setState(() => _register = true)),
      ],
    );
  }

  Widget _roleSwitch() {
    return Row(
      children: [
        _chip('Join as client', _role == 'client', () => setState(() => _role = 'client')),
        const SizedBox(width: 8),
        _chip('Join as vendor', _role == 'vendor', () => setState(() => _role = 'vendor')),
      ],
    );
  }

  Widget _chip(String label, bool selected, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF1A120C) : const Color(0xFF141518),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2D34)),
          ),
          child: Text(label, style: TextStyle(color: selected ? LensColors.primary : Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
        ),
      ),
    );
  }

  Widget _field(TextEditingController controller, String hint, IconData icon, {bool secret = false, TextInputType? keyboard}) {
    return TextField(
      controller: controller,
      obscureText: secret,
      keyboardType: keyboard,
      style: const TextStyle(color: Colors.white),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: LensColors.slate),
        prefixIcon: Icon(icon, color: LensColors.slate),
        filled: true,
        fillColor: const Color(0xFF141518),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFF2A2D34))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: LensColors.primary)),
      ),
    );
  }
}
