import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/demo_bootstrap.dart';
import 'package:lens/core/models/home_data.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/features/profile/addresses_page.dart';
import 'package:lens/features/profile/app_settings_page.dart';
import 'package:lens/features/profile/edit_profile_page.dart';
import 'package:lens/features/profile/messages_page.dart';
import 'package:lens/features/profile/payment_methods_page.dart';
import 'package:lens/features/profile/report_issue_page.dart';

void main() {
  setUp(() {
    LensConfig.useNetwork = false;
    SessionStore.instance.reset();
  });

  HomeData home() => HomeData.fromJson(DemoBootstrap.payload());

  testWidgets('payment methods lists saved cards and add actions', (tester) async {
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: PaymentMethodsPage(home: home())));
    expect(find.text('Payment Methods'), findsOneWidget);
    expect(find.textContaining('Visa'), findsOneWidget);
    expect(find.text('Add bank card'), findsOneWidget);
    expect(find.text('Add mobile wallet'), findsOneWidget);
    expect(find.text('Add PayPal'), findsOneWidget);
  });

  testWidgets('addresses can add a pin from the map picker', (tester) async {
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: const AddressesPage()));
    expect(find.text('Addresses'), findsOneWidget);
    expect(find.text('Zamalek, Cairo'), findsOneWidget);
    await tester.tap(find.text('Add address from map'));
    await tester.pumpAndSettle();
    expect(find.text('Pin on Google Maps'), findsOneWidget);
  });

  testWidgets('messages picks a project then shows contact actions', (tester) async {
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: MessagesPage(home: home())));
    expect(find.text('Messages'), findsOneWidget);
    expect(find.text('Yasmin Hall wedding'), findsOneWidget);
    expect(find.text('Call Now'), findsOneWidget);
    expect(find.text('WhatsApp'), findsOneWidget);
    expect(find.text('Chat in App'), findsOneWidget);
    await tester.tap(find.text('Chat in App'));
    await tester.pumpAndSettle();
    expect(find.text('Type a message...'), findsOneWidget);
  });

  testWidgets('app settings exposes alerts, privacy, and about the app', (tester) async {
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: AppSettingsPage(home: home())));
    expect(find.text('App Settings'), findsOneWidget);
    expect(find.text('Push notifications'), findsOneWidget);
    expect(find.text('Checkout'), findsNothing);
    expect(find.text('Available in this build'), findsNothing);
    expect(find.text('App language'), findsNothing);
    expect(find.text('English'), findsNothing);
    await tester.scrollUntilVisible(find.text('What is Lens?'), 240);
    await tester.pumpAndSettle();
    expect(find.text('About App'), findsOneWidget);
    expect(find.textContaining('Lens 1.0.0'), findsOneWidget);
    expect(find.text('What is Lens?'), findsOneWidget);
    await tester.ensureVisible(find.text('What is Lens?'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('What is Lens?'));
    await tester.pumpAndSettle();
    expect(find.textContaining('find, book, and chat'), findsOneWidget);
  });

  testWidgets('edit profile shows admin account fields and saves them', (tester) async {
    await SessionStore.instance.login(email: 'client@lens.app', password: 'password');
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: EditProfilePage(home: home())));
    expect(find.text('Edit Profile'), findsOneWidget);
    expect(find.text('Sarah Bennett'), findsWidgets);
    expect(find.textContaining('Cairo'), findsWidgets);
    expect(find.text('Active account'), findsOneWidget);
    expect(find.text('Personal details'), findsOneWidget);
    expect(find.text('Payment methods'), findsOneWidget);
    expect(find.text('Addresses'), findsOneWidget);
    await tester.enterText(find.byType(TextField).first, 'Nora Adel');
    await tester.tap(find.text('Save'));
    await tester.pumpAndSettle();
    expect(SessionStore.instance.account?.name, 'Nora Adel');
  });

  testWidgets('report an issue sends a ticket to support', (tester) async {
    await SessionStore.instance.login(email: 'client@lens.app', password: 'password');
    await tester.pumpWidget(MaterialApp(theme: LensTheme.dark(), home: const ReportIssuePage()));
    await tester.pumpAndSettle();
    expect(find.text('Report an issue'), findsOneWidget);
    expect(find.text('Lens support'), findsOneWidget);
    expect(find.text('Send report'), findsOneWidget);
    await tester.tap(find.text('Payment'));
    await tester.pump();
    final fields = find.byType(TextField);
    expect(fields, findsNWidgets(2));
    await tester.enterText(fields.at(0), 'Escrow is stuck');
    await tester.enterText(fields.at(1), 'Paid last night and still pending.');
    await tester.pump();
    await tester.ensureVisible(find.text('Send report'));
    await tester.tap(find.text('Send report'));
    await tester.pumpAndSettle();
    expect(find.text('Ticket sent.'), findsOneWidget);
    expect(find.textContaining('ISS-'), findsWidgets);
    expect(find.text('Back to profile'), findsOneWidget);
  });
}
