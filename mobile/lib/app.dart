import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/features/splash/splash_page.dart';

class LensApp extends StatelessWidget {
  const LensApp({super.key});

  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion(
      value: LensTheme.overlay,
      child: MaterialApp(
        title: 'Lens',
        navigatorKey: navigatorKey,
        debugShowCheckedModeBanner: false,
        theme: LensTheme.dark(),
        themeMode: ThemeMode.dark,
        home: const SplashPage(),
      ),
    );
  }
}
