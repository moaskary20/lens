import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/shell/app_shell.dart';

class ProfileScaffold extends StatelessWidget {
  const ProfileScaffold({
    super.key,
    required this.title,
    required this.child,
    this.action,
  });

  final String title;
  final Widget child;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AppShell.navFab(context),
      bottomNavigationBar: AppShell.navBar(context, index: AppShell.profileIndex),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(4, 4, 12, 8),
              child: Row(
                children: [
                  IconButton(
                    tooltip: 'Back',
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.chevron_left_rounded, color: LensColors.cream, size: 30),
                  ),
                  Expanded(
                    child: Text(title, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
                  ),
                  if (action != null) action!,
                ],
              ),
            ),
            Expanded(child: child),
          ],
        ),
      ),
    );
  }
}
