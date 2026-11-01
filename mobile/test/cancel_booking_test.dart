import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  setUp(() async {
    SharedPreferences.setMockInitialValues({});
    LensConfig.useNetwork = false;
    await SessionStore.instance.reset();
    await SessionStore.instance.login(
      email: 'client@lens.app',
      password: 'password',
    );
  });

  testWidgets('client cancel booking opens policy dialog', (tester) async {
    final home = HomeData.fromJson(DemoBootstrap.payload());
    await tester.pumpWidget(
      MaterialApp(
        theme: LensTheme.dark(),
        home: BookingsPage(home: home, asRoute: true),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Cancel Booking'), findsWidgets);
    await tester.tap(find.text('Cancel Booking').first);
    await tester.pumpAndSettle();
    expect(find.text('Cancel this booking?'), findsOneWidget);
    expect(find.text('Your refund'), findsOneWidget);
    expect(find.textContaining('Client cancel'), findsOneWidget);
    expect(find.text('Keep booking'), findsOneWidget);
    await tester.enterText(find.byType(TextField), 'Plans changed');
    await tester.tap(find.widgetWithText(FilledButton, 'Cancel booking'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Cancelled under'), findsOneWidget);
  });
}
