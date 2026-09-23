import 'package:flutter/material.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/auth/role_choice_page.dart';

class LoginView extends StatefulWidget {
  const LoginView({
    super.key,
    this.intendedRole = 'client',
    this.startInRegister = false,
    this.onSuccess,
  });

  final String intendedRole;
  final bool startInRegister;
  final VoidCallback? onSuccess;

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  static const _asset = 'lib/assits/login.png';

  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  late bool _register;
  bool _hidePassword = true;
  bool _busy = false;
  String? _error;
  late String _role;

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
      setState(() => _error = error.toString().replaceFirst('Exception: ', '').replaceFirst('ApiException: ', ''));
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  void _soon(String label) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$label is coming soon.'), backgroundColor: const Color(0xFF1A1B1F)),
    );
  }

  Future<void> _openRoleChoice() async {
    final role = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(builder: (_) => RoleChoicePage(initialRole: _role == 'vendor' ? 'vendor' : 'user')),
    );
    if (!mounted || role == null) {
      return;
    }
    setState(() {
      _register = true;
      _role = role == 'vendor' ? 'vendor' : 'client';
      _error = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Image.asset(
          _asset,
          fit: BoxFit.cover,
          alignment: const Alignment(0.9, -0.15),
          filterQuality: FilterQuality.high,
          errorBuilder: (_, __, ___) => const ColoredBox(color: Color(0xFF070707)),
        ),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [Color(0x66000000), Color(0xB8000000), Color(0xF2070707)],
              stops: [0, 0.36, 0.62],
            ),
          ),
        ),
        const DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.centerRight,
              end: Alignment.centerLeft,
              colors: [Color(0x00000000), Color(0x99000000)],
            ),
          ),
        ),
        SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(24, 10, 24, 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Lens', style: TextStyle(color: LensColors.primary, fontSize: 34, fontWeight: FontWeight.w800, height: 1)),
                const SizedBox(height: 4),
                const Text('Find. Book. Create.', style: TextStyle(color: Color(0xFFB8B3AB), fontSize: 13, fontWeight: FontWeight.w600)),
                const SizedBox(height: 18),
                const Text(
                  'CREATIVE PEOPLE          CREATE · BOOK · BELONG',
                  style: TextStyle(color: Color(0x66F2EFE9), fontSize: 9, letterSpacing: 1.1, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 3),
                const Text(
                  'BRIGHTER STORIES',
                  style: TextStyle(color: Color(0x66F2EFE9), fontSize: 9, letterSpacing: 1.1, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 22),
                Container(width: 18, height: 2, color: LensColors.primary),
                const SizedBox(height: 18),
                Text.rich(
                  TextSpan(
                    style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.w800, height: 1.05),
                    children: [
                      TextSpan(text: _register ? 'Create your\n' : 'Welcome\n'),
                      const TextSpan(text: 'to '),
                      const TextSpan(text: 'Lens', style: TextStyle(color: LensColors.primary)),
                    ],
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  _register
                      ? 'Join as a client or vendor and start booking on Lens.'
                      : 'Sign in to book photographers, studios, models and more.',
                  style: const TextStyle(color: Color(0xFFC4BFB7), fontSize: 15, height: 1.4),
                ),
                const SizedBox(height: 28),
                if (_register) ...[
                  _field(_name, 'Full name', Icons.person_outline_rounded),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _roleChip('Join as client', _role == 'client', () => setState(() => _role = 'client')),
                      const SizedBox(width: 10),
                      _roleChip('Join as vendor', _role == 'vendor', () => setState(() => _role = 'vendor')),
                    ],
                  ),
                  const SizedBox(height: 12),
                ],
                _field(_email, 'Email', Icons.mail_outline_rounded, keyboard: TextInputType.emailAddress),
                const SizedBox(height: 12),
                _field(
                  _password,
                  'Password',
                  Icons.lock_outline_rounded,
                  secret: _hidePassword,
                  suffix: IconButton(
                    onPressed: () => setState(() => _hidePassword = !_hidePassword),
                    icon: Icon(_hidePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined, color: const Color(0xFF8E8B84)),
                  ),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Color(0xFFFF6B6B), fontWeight: FontWeight.w600)),
                ],
                const SizedBox(height: 18),
                _primaryButton(
                  label: _register ? 'Create an Account' : 'Sign In',
                  onPressed: _busy ? null : _submit,
                ),
                const SizedBox(height: 22),
                const Row(
                  children: [
                    Expanded(child: Divider(color: Color(0xFF2A2A2E))),
                    Padding(
                      padding: EdgeInsets.symmetric(horizontal: 12),
                      child: Text('Or continue with', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 13)),
                    ),
                    Expanded(child: Divider(color: Color(0xFF2A2A2E))),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(child: _social(icon: Icons.apple, label: 'Continue with Apple', onTap: () => _soon('Apple sign in'))),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _social(
                        leading: const Text('G', style: TextStyle(color: Color(0xFF4285F4), fontWeight: FontWeight.w800, fontSize: 18)),
                        label: 'Continue with Google',
                        onTap: () => _soon('Google sign in'),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                _secondaryButton(
                  label: _register ? 'Sign In' : 'Create an Account',
                  onPressed: () {
                    if (_register) {
                      setState(() {
                        _register = false;
                        _error = null;
                      });
                      return;
                    }
                    _openRoleChoice();
                  },
                ),
                const SizedBox(height: 18),
                Center(
                  child: TextButton(
                    onPressed: () => _soon('Password reset'),
                    child: const Text('Forgot password?', style: TextStyle(color: Color(0xFF8E8B84), fontSize: 14)),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _field(
    TextEditingController controller,
    String hint,
    IconData icon, {
    bool secret = false,
    TextInputType? keyboard,
    Widget? suffix,
  }) {
    return TextField(
      controller: controller,
      obscureText: secret,
      keyboardType: keyboard,
      style: const TextStyle(color: Colors.white, fontSize: 15),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Color(0xFF8E8B84), fontWeight: FontWeight.w500),
        prefixIcon: Icon(icon, color: const Color(0xFF8E8B84)),
        suffixIcon: suffix,
        filled: true,
        fillColor: const Color(0xE6121214),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(99),
          borderSide: const BorderSide(color: Color(0xFF2A2A2E)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(99),
          borderSide: const BorderSide(color: LensColors.primary),
        ),
      ),
    );
  }

  Widget _primaryButton({required String label, VoidCallback? onPressed}) {
    return SizedBox(
      height: 54,
      width: double.infinity,
      child: FilledButton(
        onPressed: onPressed,
        style: FilledButton.styleFrom(
          backgroundColor: LensColors.primary,
          foregroundColor: Colors.white,
          disabledBackgroundColor: LensColors.primary.withValues(alpha: 0.5),
          elevation: 0,
          shape: const StadiumBorder(),
        ),
        child: _busy
            ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
            : Stack(
                alignment: Alignment.center,
                children: [
                  Text(label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                  const Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 26)),
                ],
              ),
      ),
    );
  }

  Widget _secondaryButton({required String label, required VoidCallback onPressed}) {
    return SizedBox(
      height: 52,
      width: double.infinity,
      child: OutlinedButton(
        onPressed: onPressed,
        style: OutlinedButton.styleFrom(
          foregroundColor: LensColors.primary,
          side: const BorderSide(color: Color(0xFF2A2A2E)),
          backgroundColor: const Color(0xE6121214),
          shape: const StadiumBorder(),
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            Text(label, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
            const Align(alignment: Alignment.centerRight, child: Icon(Icons.chevron_right_rounded, size: 22)),
          ],
        ),
      ),
    );
  }

  Widget _social({required String label, required VoidCallback onTap, IconData? icon, Widget? leading}) {
    return SizedBox(
      height: 48,
      child: OutlinedButton(
        onPressed: onTap,
        style: OutlinedButton.styleFrom(
          foregroundColor: Colors.white,
          side: const BorderSide(color: Color(0xFF2A2A2E)),
          backgroundColor: const Color(0xE6121214),
          shape: const StadiumBorder(),
          padding: const EdgeInsets.symmetric(horizontal: 10),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            if (icon != null) Icon(icon, size: 18),
            if (leading != null) leading,
            const SizedBox(width: 6),
            Flexible(
              child: Text(label, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _roleChip(String label, bool selected, VoidCallback onTap) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          height: 42,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: selected ? const Color(0xFF1A120C) : const Color(0xE6121214),
            borderRadius: BorderRadius.circular(99),
            border: Border.all(color: selected ? LensColors.primary : const Color(0xFF2A2A2E)),
          ),
          child: Text(label, style: TextStyle(color: selected ? LensColors.primary : Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
        ),
      ),
    );
  }
}
