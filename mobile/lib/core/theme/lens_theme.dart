import 'package:flutter/material.dart';
import 'package:lens/core/theme/lens_colors.dart';

class LensTheme {
  const LensTheme._();

  static ThemeData dark() {
    const scheme = ColorScheme.dark(
      primary: LensColors.primary,
      onPrimary: LensColors.cream,
      secondary: LensColors.warning,
      error: LensColors.primaryDeep,
      surface: LensColors.dark,
      onSurface: LensColors.cream,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: scheme,
      scaffoldBackgroundColor: LensColors.charcoal,
      fontFamily: 'Roboto',
      appBarTheme: const AppBarTheme(
        backgroundColor: LensColors.charcoal,
        foregroundColor: LensColors.cream,
        elevation: 0,
        centerTitle: false,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: LensColors.primary,
          foregroundColor: LensColors.cream,
          minimumSize: const Size.fromHeight(48),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
    );
  }
}
