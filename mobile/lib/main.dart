import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:lens/app.dart';
import 'package:lens/core/push_alerts.dart';
import 'package:lens/core/theme/lens_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(LensTheme.overlay);
  await PushAlerts.instance.bootstrap();
  runApp(const LensApp());
}
