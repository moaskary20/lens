import 'package:flutter/material.dart';
import 'package:lens/features/auth/login_view.dart';
import 'package:lens/features/shell/app_shell.dart';

class AuthPage extends StatelessWidget {
  const AuthPage({super.key, required this.bootstrap, this.intendedRole = 'client'});

  final Map<String, dynamic> bootstrap;
  final String intendedRole;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF070707),
      body: LoginView(
        intendedRole: intendedRole,
        onSuccess: () => Navigator.of(context).pushReplacement(
          MaterialPageRoute<void>(builder: (_) => AppShell(bootstrap: bootstrap)),
        ),
      ),
    );
  }
}
