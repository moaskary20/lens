import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/app.dart';
import 'package:lens/core/theme/lens_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(LensTheme.overlay);
  runApp(const LensApp());
}
