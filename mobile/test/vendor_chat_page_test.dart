import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/features/home/vendor_chat_page.dart';

void main() {
  setUp(() => LensConfig.useNetwork = false);

  testWidgets('in-app chat matches the booked conversation layout', (tester) async {
    final home = HomeData.fromJson(DemoBootstrap.payload());
    final vendor = home.popular.first.vendors.first;

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: VendorChatPage(
        vendor: vendor,
        projectName: 'Food Photography Session',
        dateLabel: 'Saturday, 19 September 2026',
        time: '2:00 PM',
        location: 'Zamalek, Cairo',
      ),
    ));

    expect(find.text(vendor.displayName), findsOneWidget);
    expect(find.text('Food Photography Session'), findsWidgets);
    expect(find.text('Confirmed'), findsOneWidget);
    expect(find.text('Zamalek, Cairo'), findsOneWidget);
    expect(find.text('Today'), findsOneWidget);
    expect(find.text('Share Reference'), findsOneWidget);
    expect(find.text('Send Location'), findsOneWidget);
    expect(find.text('Project Details'), findsOneWidget);
    expect(find.text('Type a message...'), findsOneWidget);
    expect(find.textContaining('looking forward to the session'), findsOneWidget);
    await tester.scrollUntilVisible(
      find.textContaining('Shot List'),
      240,
      scrollable: find.descendant(of: find.byType(VendorChatPage), matching: find.byType(Scrollable)).first,
    );
    expect(find.textContaining('Shot List'), findsOneWidget);
    await tester.enterText(find.byType(TextField), 'See you then');
    await tester.tap(find.byIcon(Icons.send_rounded));
    await tester.pump();
    expect(find.text('See you then'), findsOneWidget);

    await tester.tap(find.text('Project Details'));
    await tester.pumpAndSettle();
    expect(find.text('Saturday, 19 September 2026 • 2:00 PM'), findsWidgets);
    await tester.tapAt(const Offset(8, 8));
    await tester.pumpAndSettle();

    await tester.tap(find.byIcon(Icons.add));
    await tester.pumpAndSettle();
    expect(find.text('Photo'), findsOneWidget);
    expect(find.text('File'), findsOneWidget);
    expect(find.text('Voice note'), findsOneWidget);
    await tester.tapAt(const Offset(8, 8));
    await tester.pumpAndSettle();

    await tester.tap(find.byIcon(Icons.more_vert_rounded));
    await tester.pumpAndSettle();
    expect(find.text('Call vendor'), findsOneWidget);
    expect(find.text('WhatsApp vendor'), findsOneWidget);
  });
}
