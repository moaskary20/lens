import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class PlaceholderPage extends StatelessWidget {
  const PlaceholderPage({super.key, required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: LensColors.charcoal,
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
