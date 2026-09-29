import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/core/theme/lens_colors.dart';

class LensTheme {
  const LensTheme._();

  static const overlay = SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarBrightness: Brightness.dark,
    statusBarIconBrightness: Brightness.light,
    systemNavigationBarColor: LensColors.charcoal,
    systemNavigationBarIconBrightness: Brightness.light,
    systemNavigationBarDividerColor: LensColors.charcoal,
  );

  static ThemeData dark() {
    const scheme = ColorScheme.dark(
      primary: LensColors.primary,
      onPrimary: LensColors.cream,
      secondary: LensColors.warning,
      error: LensColors.primaryDeep,
      surface: LensColors.charcoal,
      onSurface: LensColors.cream,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      colorScheme: scheme,
      scaffoldBackgroundColor: LensColors.charcoal,
      canvasColor: LensColors.charcoal,
      cardColor: LensColors.dark,
      fontFamily: 'Roboto',
      appBarTheme: const AppBarTheme(
        backgroundColor: LensColors.charcoal,
        foregroundColor: LensColors.cream,
        elevation: 0,
        centerTitle: false,
        systemOverlayStyle: overlay,
      ),
      bottomAppBarTheme: const BottomAppBarThemeData(
        color: LensColors.charcoal,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
      ),
      drawerTheme: const DrawerThemeData(backgroundColor: LensColors.charcoal),
      dialogTheme: const DialogThemeData(backgroundColor: LensColors.charcoal),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: LensColors.charcoal,
        surfaceTintColor: Colors.transparent,
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
