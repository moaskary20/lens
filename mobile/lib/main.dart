import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/app.dart';
import 'package:lens/core/push_alerts.dart';
import 'package:lens/core/theme/lens_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(LensTheme.overlay);
  try {
    await PushAlerts.instance.bootstrap();
  } catch (_) {
    // Never block app launch if local notifications fail to initialize.
  }
  runApp(const LensApp());
}
