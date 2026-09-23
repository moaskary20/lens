import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/features/splash/splash_page.dart';

class LensApp extends StatelessWidget {
  const LensApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Lens',
      debugShowCheckedModeBanner: false,
      theme: LensTheme.dark(),
      themeMode: ThemeMode.dark,
      home: const SplashPage(),
    );
  }
}
