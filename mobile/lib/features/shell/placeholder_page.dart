import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';
import 'package:lens/features/shell/app_shell.dart';

class PlaceholderPage extends StatelessWidget {
  const PlaceholderPage({super.key, required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,
      floatingActionButton: AppShell.navFab(context),
      bottomNavigationBar: AppShell.navBar(context, index: 0),
      appBar: AppBar(title: Text(title)),
      body: Center(
        child: Text(
          '$title is coming next.',
          style: const TextStyle(color: LensColors.cream),
        ),
      ),
    );
  }
}
