import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:lens/app.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/favorites_store.dart';
import 'package:lens/core/notifications_store.dart';
import 'package:lens/core/session_store.dart';
import 'package:lens/core/theme/lens_theme.dart';
import 'package:lens/features/home/favorite_heart.dart';
import 'package:lens/features/home/filter_page.dart';
import 'package:lens/features/home/book_checkout_page.dart';
import 'package:lens/features/home/book_confirmed_page.dart';
import 'package:lens/features/home/book_date_page.dart';
import 'package:lens/features/home/book_project_page.dart';
import 'package:lens/features/home/book_review_page.dart';
import 'package:lens/features/bookings/bookings_page.dart';
import 'package:lens/features/home/vendor_chat_page.dart';
import 'package:lens/features/home/vendor_profile_page.dart';
import 'package:lens/features/auth/user_register_page.dart';
import 'package:lens/features/auth/vendor_register_page.dart';
import 'package:lens/features/onboarding/onboarding_page.dart';
import 'package:lens/features/shell/app_shell.dart';

void main() {
  setUp(() {
    LensConfig.useNetwork = false;
    FavoritesStore.instance.reset();
    NotificationsStore.instance.reset();
    SessionStore.instance.reset();
  });

  testWidgets('splash shows the Lens mark', (WidgetTester tester) async {
    await tester.pumpWidget(const LensApp());
    expect(find.text('Lens'), findsOneWidget);
    expect(find.text('Create. Connect. Deliver.'), findsOneWidget);
  });

  testWidgets('onboarding introduces the idea flow', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: OnboardingPage(bootstrap: _bootstrap()),
    ));

    expect(find.text('Skip'), findsOneWidget);
    expect(find.text('Next'), findsOneWidget);
    expect(find.image(const AssetImage('lib/assits/onboard1.png')), findsOneWidget);
    expect(find.textContaining('an idea.'), findsOneWidget);
    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    expect(find.textContaining('confidence.'), findsOneWidget);
    expect(find.image(const AssetImage('lib/assits/onboard2.png')), findsOneWidget);
    await tester.tap(find.text('Skip'));
    await tester.pumpAndSettle();
    expect(find.text('Find. Book. Create.'), findsOneWidget);
  });

  testWidgets('onboarding role step continues as guest', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: OnboardingPage(bootstrap: _bootstrap()),
    ));

    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    expect(find.textContaining('How will you use'), findsOneWidget);
    expect(find.text('Join as User'), findsOneWidget);
    expect(find.text('Join as Vendor'), findsOneWidget);
    await tester.tap(find.text('Join as Vendor'));
    await tester.pump();
    await tester.tap(find.text('Continue as a guest'));
    await tester.pumpAndSettle();
    expect(find.text('Find. Book. Create.'), findsOneWidget);
  });

  testWidgets('home follows enabled admin features', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: AppShell(bootstrap: _bootstrap()),
    ));

    expect(find.text('Lens'), findsOneWidget);
    expect(find.text('Find. Book. Create.'), findsOneWidget);
    expect(find.text('AI Search'), findsOneWidget);
    expect(find.text('Photographers'), findsOneWidget);
    expect(find.text('AI Videos & Motion'), findsNothing);
    expect(find.text('VO (Voice Over)'), findsNothing);
    expect(find.text('Fahad Studio Light', skipOffstage: false), findsOneWidget);
    expect(find.text('Featured Studios', skipOffstage: false), findsOneWidget);
    expect(find.byIcon(Icons.favorite_border_rounded), findsWidgets);
    expect(find.byIcon(Icons.menu_rounded), findsOneWidget);
    await tester.tap(find.byTooltip('Favorites'));
    await tester.pumpAndSettle();
    expect(find.text('Favorites'), findsOneWidget);
    expect(find.text('No saved creators yet'), findsOneWidget);
    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    await tester.tap(find.byType(FavoriteHeart).first);
    await tester.pump();
    expect(find.text('1'), findsWidgets);
    await tester.tap(find.byTooltip('Notifications'));
    await tester.pumpAndSettle();
    expect(find.text('Notifications'), findsOneWidget);
    expect(find.text('Request accepted'), findsOneWidget);
    expect(find.text('Mark all read'), findsOneWidget);
    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    expect(find.text('Bookings'), findsOneWidget);
    await tester.tap(find.text('Bookings'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Welcome'), findsOneWidget);
    expect(find.text('Create an Account'), findsOneWidget);
    expect(find.image(const AssetImage('lib/assits/login.png')), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'client@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.tap(find.widgetWithText(FilledButton, 'Sign In'));
    await tester.pumpAndSettle();
    expect(find.text('My Bookings'), findsOneWidget);
    expect(find.text('Lana Mostafa'), findsOneWidget);
    expect(find.text('Cancel Booking'), findsWidgets);
    await tester.tap(find.text('Home'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Search'));
    await tester.pumpAndSettle();
    expect(find.text('What are you looking to create?'), findsOneWidget);
    expect(find.text('Explore near you'), findsOneWidget);
    expect(find.text('Recommended for you'), findsOneWidget);
    expect(find.text('View Map'), findsOneWidget);
    await tester.tap(find.text('Home'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Search photographers, studios, models...'));
    await tester.pumpAndSettle();
    expect(find.text('What are you looking to create?'), findsOneWidget);
    await tester.tap(find.text('Home'));
    await tester.pumpAndSettle();

    await tester.tap(find.byIcon(Icons.menu_rounded));
    await tester.pumpAndSettle();
    expect(find.byIcon(Icons.close_rounded), findsOneWidget);
    await tester.tap(find.byIcon(Icons.close_rounded));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Photographers'));
    await tester.pumpAndSettle();
    expect(find.text('Sort'), findsOneWidget);
    expect(find.text('Filter'), findsOneWidget);
    expect(find.text('Map'), findsOneWidget);
    expect(find.text('Recommended'), findsOneWidget);
    expect(find.text('Fahad Studio Light'), findsOneWidget);
    await tester.tap(find.text('Fahad Studio Light'));
    await tester.pumpAndSettle();
    expect(find.text('Book Now'), findsOneWidget);
    await tester.ensureVisible(find.text('Book Now'));
    await tester.tap(find.text('Book Now'));
    await tester.pumpAndSettle();
    expect(find.text('Choose Your Date'), findsOneWidget);
    expect(find.text('September 2026'), findsOneWidget);
    expect(find.text('Continue'), findsOneWidget);
    await tester.ensureVisible(find.text('Continue'));
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Tell them about your project'), findsOneWidget);
    expect(find.text('Shoot type *'), findsOneWidget);
    final projectScroll = find.descendant(of: find.byType(BookProjectPage), matching: find.byType(Scrollable)).first;
    await tester.enterText(find.widgetWithText(TextField, 'E.g. Summer Menu Campaign'), 'Summer Menu Campaign');
    await tester.tap(find.text('Select Shoot type'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Wedding'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Select visual style'), 240, scrollable: projectScroll);
    await tester.tap(find.text('Select visual style'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Natural light'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Select deliverables'), 240, scrollable: projectScroll);
    await tester.tap(find.text('Select deliverables'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('25 edited photos'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Half-day price (6 hours)'), 240, scrollable: projectScroll);
    await tester.tap(find.text('Half-day price (6 hours)'));
    await tester.pump();
    await tester.scrollUntilVisible(find.text('Pin on Google Maps'), 240, scrollable: projectScroll);
    await tester.tap(find.text('Pin on Google Maps'));
    await tester.pumpAndSettle();
    expect(find.text('Pin on Google Maps'), findsWidgets);
    await tester.tap(find.text('Zamalek, Cairo'));
    await tester.pump();
    await tester.tap(find.text('Use this location'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(
      find.widgetWithText(TextField, 'Tell us about your project, goals, and what you have in mind...'),
      240,
      scrollable: find.descendant(of: find.byType(BookProjectPage), matching: find.byType(Scrollable)).first,
    );
    await tester.enterText(
      find.widgetWithText(TextField, 'Tell us about your project, goals, and what you have in mind...'),
      'Lifestyle plates and close-ups of the new summer menu.',
    );
    expect(find.text('Review Booking'), findsOneWidget);
    await tester.ensureVisible(find.text('Review Booking'));
    await tester.tap(find.text('Review Booking'));
    await tester.pumpAndSettle();
    expect(find.text('Review Your Booking'), findsOneWidget);
    final reviewScroll = find.descendant(of: find.byType(BookReviewPage), matching: find.byType(Scrollable)).first;
    await tester.scrollUntilVisible(find.text('Zamalek, Cairo'), 240, scrollable: reviewScroll);
    expect(find.text('Zamalek, Cairo'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Summer Menu Campaign'), 240, scrollable: reviewScroll);
    expect(find.text('Summer Menu Campaign'), findsOneWidget);
    expect(find.text('Wedding'), findsOneWidget);
    expect(find.text('Natural light'), findsOneWidget);
    expect(find.text('25 edited photos'), findsOneWidget);
    expect(find.text('Half-day price (6 hours)'), findsWidgets);
    await tester.scrollUntilVisible(find.textContaining('Lifestyle plates'), 240, scrollable: reviewScroll);
    expect(find.textContaining('Lifestyle plates'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Lens platform fee (10%)'), 240, scrollable: reviewScroll);
    expect(find.text('Lens platform fee (10%)'), findsOneWidget);
    expect(find.text('Continue to Payment'), findsOneWidget);
    await tester.ensureVisible(find.text('Continue to Payment'));
    await tester.tap(find.text('Continue to Payment'));
    await tester.pumpAndSettle();
    expect(find.text('Secure Checkout'), findsOneWidget);
    expect(find.text('Summer Menu Campaign'), findsOneWidget);
    expect(find.textContaining('Zamalek'), findsWidgets);
    final checkoutScroll = find.descendant(of: find.byType(BookCheckoutPage), matching: find.byType(Scrollable)).first;
    await tester.scrollUntilVisible(find.text('Bank card'), 280, scrollable: checkoutScroll);
    expect(find.text('Bank card'), findsOneWidget);
    expect(find.text('Mobile wallet'), findsOneWidget);
    expect(find.text('PayPal'), findsWidgets);
    await tester.tap(find.text('Bank card'));
    await tester.pumpAndSettle();
    expect(find.text('Cardholder name'), findsOneWidget);
    await tester.enterText(find.byKey(const Key('card-holder')), 'Sarah Bennett');
    await tester.enterText(find.byKey(const Key('card-number')), '4242424242424242');
    await tester.enterText(find.byKey(const Key('card-expiry')), '1228');
    await tester.enterText(find.byKey(const Key('card-cvv')), '123');
    await tester.ensureVisible(find.text('Save Bank card'));
    await tester.tap(find.text('Save Bank card'));
    await tester.pumpAndSettle();
    expect(find.byType(BookCheckoutPage), findsOneWidget);
    expect(find.textContaining('Visa'), findsWidgets);
    await tester.scrollUntilVisible(find.text('Promo code'), 240, scrollable: find.descendant(of: find.byType(BookCheckoutPage), matching: find.byType(Scrollable)).first);
    expect(find.text('Promo code'), findsOneWidget);
    expect(find.text('Confirm & Pay'), findsOneWidget);
    await tester.ensureVisible(find.text('Confirm & Pay'));
    await tester.tap(find.text('Confirm & Pay'));
    await tester.pumpAndSettle();
    expect(find.text("You're booked."), findsOneWidget);
    expect(find.text('View Booking'), findsOneWidget);
    expect(find.text('Message Creator'), findsOneWidget);
    await tester.tap(find.text('Message Creator'));
    await tester.pumpAndSettle();
    expect(find.byType(VendorChatPage), findsOneWidget);
    expect(find.text('Type a message...'), findsOneWidget);
    Navigator.of(tester.element(find.byType(VendorChatPage))).pop();
    await tester.pumpAndSettle();
    expect(find.text("You're booked."), findsOneWidget);
    await tester.tap(find.text('View Booking'));
    await tester.pumpAndSettle();
    expect(find.byType(BookingsPage), findsOneWidget);
    expect(find.text('My Bookings'), findsOneWidget);
    expect(find.text("You're booked."), findsNothing);
    expect(find.byType(VendorChatPage), findsNothing);
    await tester.tap(find.descendant(of: find.byType(BookingsPage), matching: find.byIcon(Icons.chevron_left_rounded)));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Photographers'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Fahad Studio Light'));
    await tester.pumpAndSettle();
    expect(find.text('Watch Showreel'), findsOneWidget);
    expect(find.text('Portfolio'), findsOneWidget);
    expect(find.text('Available this weekend'), findsOneWidget);
    expect(find.text('Book Now'), findsOneWidget);
    expect(find.text('Call Now'), findsNothing);
    expect(find.text('WhatsApp'), findsNothing);
    expect(find.text('Chat in App'), findsNothing);
    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Sort'));
    await tester.pumpAndSettle();
    expect(find.text('Top Rated'), findsOneWidget);
    expect(find.text('Best Review'), findsOneWidget);
    expect(find.text('Lowest to Highest'), findsOneWidget);
    expect(find.text('Highest to Lowest'), findsOneWidget);
    expect(find.text('Apply'), findsOneWidget);
    expect(find.text('Clear All'), findsOneWidget);
    await tester.tap(find.byIcon(Icons.close));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Filter'));
    await tester.pumpAndSettle();
    expect(find.text('Filter Photographers'), findsOneWidget);
    expect(find.text('Reset All'), findsOneWidget);
    expect(find.text('Service Type'), findsOneWidget);
    expect(find.text('Location'), findsOneWidget);
    expect(find.text('Price Range (EGP)', skipOffstage: false), findsOneWidget);
    expect(find.text('Minimum Rating', skipOffstage: false), findsOneWidget);
    expect(find.text('Availability', skipOffstage: false), findsOneWidget);
    expect(find.text('Apply Filters'), findsOneWidget);
    expect(find.text('Cancel'), findsOneWidget);
    await tester.tap(find.text('Cancel'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Map'));
    await tester.pumpAndSettle();
    expect(find.text('Search in this area'), findsOneWidget);
    expect(find.text('List'), findsOneWidget);
    expect(find.text('All Types'), findsOneWidget);
    expect(find.textContaining('Sort by'), findsOneWidget);
  });

  testWidgets('home hides AI and bookings when features are off', (WidgetTester tester) async {
    final payload = _bootstrap();
    (payload['features'] as Map<String, bool>)
      ..['ai_assistant'] = false
      ..['bookings'] = false
      ..['notifications'] = false
      ..['filters'] = false;

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: AppShell(bootstrap: payload),
    ));

    expect(find.text('AI Search'), findsNothing);
    expect(find.text('Bookings'), findsNothing);
    expect(find.text('Photographers'), findsOneWidget);
  });

  testWidgets('profile tab matches the user account screen', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: AppShell(bootstrap: _bootstrap()),
    ));

    await tester.tap(find.text('Profile'));
    await tester.pumpAndSettle();
    expect(find.text('Guest'), findsOneWidget);
    expect(find.text('Sign in to manage bookings'), findsOneWidget);
    expect(find.text('Sign in'), findsWidgets);
    expect(find.text('Upcoming'), findsOneWidget);
    expect(find.text('My Bookings'), findsOneWidget);
    expect(find.text('Saved Creators'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Log Out'), 240);
    expect(find.text('Log Out'), findsOneWidget);
  });

  testWidgets('bookings tab shows incoming list after vendor sign in', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: AppShell(bootstrap: _bootstrap()),
    ));

    await tester.tap(find.text('Bookings'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Welcome'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'vendor@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.tap(find.widgetWithText(FilledButton, 'Sign In'));
    await tester.pumpAndSettle();
    expect(find.text('Incoming bookings'), findsOneWidget);
    expect(find.text('Sarah Bennett'), findsWidgets);
    expect(find.text('LN-1003'), findsOneWidget);
  });

  testWidgets('onboarding continue opens registration for the chosen role', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: OnboardingPage(bootstrap: _bootstrap()),
    ));

    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Next'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Join as Vendor'));
    await tester.pump();
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Identity'), findsOneWidget);
    expect(find.text('Vendor type *'), findsOneWidget);
    expect(find.text('Continue'), findsOneWidget);
    expect(find.text('1'), findsOneWidget);
    expect(find.text('5'), findsOneWidget);

    await tester.tap(find.byIcon(Icons.chevron_left_rounded));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Join as User'));
    await tester.pump();
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Create your account'), findsOneWidget);
    expect(find.text('Continue'), findsOneWidget);
    expect(find.text('2'), findsOneWidget);
  });

  testWidgets('vendor register continues through numbered admin steps', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: VendorRegisterPage(bootstrap: _bootstrap()),
    ));

    expect(find.text('Identity'), findsOneWidget);
    expect(find.text('1'), findsOneWidget);
    expect(find.text('5'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Full name'), 'Yasmin Lens');
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'yasmin@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Phone 010 / 011 / 012 / 015'), '01055550011');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.ensureVisible(find.text('Photographers'));
    await tester.tap(find.text('Photographers'));
    await tester.pump();
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Photographer profile'), findsOneWidget);
    expect(find.text('Camera type'), findsOneWidget);
    expect(find.text('Lenses available'), findsOneWidget);
    expect(find.text('Primary specialties'), findsOneWidget);
    expect(find.text('Wedding'), findsOneWidget);
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Previous projects'), findsOneWidget);
    expect(find.text('Add previous project'), findsOneWidget);
    await tester.tap(find.text('Add previous project'));
    await tester.pump();
    expect(find.text('Add photos / gallery'), findsOneWidget);
    await tester.tap(find.text('Video'));
    await tester.pump();
    expect(find.text('Add videos'), findsOneWidget);
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Pricing'), findsOneWidget);
    expect(find.text('Pricing model'), findsWidgets);
    expect(find.text('Half-day price (6 hours)'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'EGP Half-day price (6 hours)'), '1800');
    await tester.enterText(find.widgetWithText(TextField, 'EGP Full-day price (12 hours)'), '3200');
    await tester.enterText(find.widgetWithText(TextField, 'Post-production turnaround (hours)'), '48');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Bank & payouts'), findsOneWidget);
    expect(find.text('Bank account'), findsOneWidget);
    expect(find.text('Mobile wallet'), findsOneWidget);
    expect(find.text('PayPal'), findsOneWidget);
    await tester.tap(find.text('Mobile wallet'));
    await tester.pump();
    await tester.enterText(find.widgetWithText(TextField, 'Wallet phone 010 / 011 / 012 / 015'), '01055550011');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(SessionStore.instance.isVendor, isTrue);
  });

  testWidgets('vendor type loads the matching type profile', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: VendorRegisterPage(bootstrap: _bootstrap()),
    ));

    await tester.enterText(find.widgetWithText(TextField, 'Full name'), 'Noor Studio');
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'noor@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Phone 010 / 011 / 012 / 015'), '01512345678');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.ensureVisible(find.text('Studios'));
    await tester.tap(find.text('Studios'));
    await tester.pump();
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Studio profile'), findsOneWidget);
    expect(find.text('Studio Type'), findsOneWidget);
    expect(find.text('Features & Equipment'), findsOneWidget);
    expect(find.text('Camera type'), findsNothing);
  });

  testWidgets('user register continues through numbered admin steps', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: UserRegisterPage(bootstrap: _bootstrap()),
    ));

    expect(find.text('Create your account'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Full name'), 'Mona Client');
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'mona@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.enterText(find.widgetWithText(TextField, 'Confirm password'), 'password');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.text('Your profile'), findsOneWidget);
    expect(find.text('Governorate'), findsOneWidget);
    expect(find.text('Language'), findsOneWidget);
    await tester.enterText(find.widgetWithText(TextField, 'Phone 010 / 011 / 012 / 015'), '01234567890');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(SessionStore.instance.isClient, isTrue);
  });

  testWidgets('user register requires an egyptian mobile number', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: UserRegisterPage(bootstrap: _bootstrap()),
    ));

    await tester.enterText(find.widgetWithText(TextField, 'Full name'), 'Mona Client');
    await tester.enterText(find.widgetWithText(TextField, 'Email'), 'mona2@lens.app');
    await tester.enterText(find.widgetWithText(TextField, 'Password'), 'password');
    await tester.enterText(find.widgetWithText(TextField, 'Confirm password'), 'password');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextField, 'Phone 010 / 011 / 012 / 015'), '01912345678');
    await tester.tap(find.text('Continue'));
    await tester.pump();
    expect(find.textContaining('Egyptian mobile number'), findsOneWidget);
    expect(SessionStore.instance.isGuest, isTrue);
  });

  testWidgets('models filter shows the admin catalog', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: const FilterPage(title: 'Models', vendorTypeSlug: 'model'),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Filter Models (Men)'), findsOneWidget);
    expect(find.text('Reset All'), findsOneWidget);
    expect(find.text('Women'), findsOneWidget);
    expect(find.text('Kids'), findsOneWidget);
    expect(find.text('Category'), findsOneWidget);
    expect(find.text('Fashion'), findsOneWidget);
    expect(find.text('Educational'), findsOneWidget);
    expect(find.text('Social Media'), findsOneWidget);
    expect(find.text('Age Range'), findsOneWidget);
    expect(find.text('Under 18'), findsOneWidget);
    expect(find.text('Height (cm)'), findsOneWidget);
    expect(find.text('Under 160'), findsOneWidget);
    expect(find.text('Size (Clothes & Shoes)'), findsOneWidget);
    expect(find.text('T-Shirt / Tops'), findsOneWidget);
    expect(find.text('Pants / Bottoms (Waist)'), findsOneWidget);
    expect(find.text('Shoes (EU)'), findsOneWidget);
    expect(find.text('45+'), findsOneWidget);
    expect(find.text('Search city or area'), findsOneWidget);
    expect(find.text('Cairo'), findsOneWidget);
    expect(find.text('6th of October'), findsOneWidget);
    expect(find.text('10th of Ramadan'), findsOneWidget);
    expect(find.text('500'), findsOneWidget);
    expect(find.text('4.0+'), findsOneWidget);
    expect(find.text('Any Date'), findsOneWidget);
    expect(find.text('Apply Filters'), findsOneWidget);
    expect(find.text('Cancel'), findsOneWidget);
  });

  testWidgets('studios filter shows the admin catalog', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: const FilterPage(title: 'Studios', vendorTypeSlug: 'studio'),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Filter Studios'), findsOneWidget);
    expect(find.text('Studio Type'), findsOneWidget);
    expect(find.text('All Types'), findsOneWidget);
    expect(find.text('Photo Studio'), findsOneWidget);
    expect(find.text('Podcast Studio'), findsOneWidget);
    expect(find.text('Event Space'), findsOneWidget);
    expect(find.text('Search city or area'), findsOneWidget);
    expect(find.text('New Cairo'), findsOneWidget);
    expect(find.text('Zamalek'), findsOneWidget);
    expect(find.text('Sheikh Zayed'), findsOneWidget);
    expect(find.text('Nasr City'), findsOneWidget);
    expect(find.text('Price Range (EGP/hour)'), findsOneWidget);
    expect(find.text('Under 100'), findsOneWidget);
    expect(find.text('2,000+'), findsOneWidget);
    expect(find.text('Studio Size (m²)'), findsOneWidget);
    expect(find.text('Under 50'), findsOneWidget);
    expect(find.text('Features & Equipment'), findsOneWidget);
    expect(find.text('All Equipment'), findsOneWidget);
    expect(find.text('Podcast Setup'), findsOneWidget);
    expect(find.text('Makeup Room'), findsOneWidget);
    expect(find.text('Natural Light'), findsOneWidget);
    expect(find.text('Any Rating'), findsOneWidget);
    expect(find.text('3.5+'), findsOneWidget);
    expect(find.text('Verified Only'), findsOneWidget);
    expect(find.text('Show only verified studios'), findsOneWidget);
    expect(find.text('Apply Filters'), findsOneWidget);
  });

  testWidgets('ugc filter shows the admin catalog', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 4200);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: const FilterPage(title: 'UGC Creators', vendorTypeSlug: 'ugc'),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Filter UGC Creators'), findsOneWidget);
    expect(find.text('Category / Niche'), findsOneWidget);
    expect(find.text('All Categories'), findsOneWidget);
    expect(find.text('Beauty'), findsOneWidget);
    expect(find.text('Food & Beverages'), findsOneWidget);
    expect(find.text('Home & Living'), findsOneWidget);
    expect(find.text('City (Egypt)'), findsOneWidget);
    expect(find.text('All Cities'), findsOneWidget);
    expect(find.text('Sharm El Sheikh'), findsOneWidget);
    expect(find.text('Accent'), findsOneWidget);
    expect(find.text('Egyptian'), findsOneWidget);
    expect(find.text('Shami (Levantine)'), findsOneWidget);
    expect(find.text('Followers Count'), findsOneWidget);
    expect(find.text('Under 1K'), findsOneWidget);
    expect(find.text('500K - 1M+'), findsOneWidget);
    expect(find.text('Price Range (EGP / video)'), findsOneWidget);
    expect(find.text('Content Type'), findsOneWidget);
    expect(find.text('Unboxing'), findsOneWidget);
    expect(find.text('Trend / Challenge'), findsOneWidget);
    expect(find.text('UGC Ads'), findsOneWidget);
    expect(find.text('Non-binary'), findsOneWidget);
    expect(find.text('Age range'), findsOneWidget);
    expect(find.text('18 - 24'), findsOneWidget);
    expect(find.text('All Languages'), findsOneWidget);
    expect(find.text('Bilingual'), findsOneWidget);
    expect(find.text('Any Time'), findsOneWidget);
    expect(find.text('Apply Filters'), findsOneWidget);
  });

  testWidgets('food stylists filter shows the admin catalog', (WidgetTester tester) async {
    tester.view.physicalSize = const Size(1080, 3600);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(MaterialApp(
      theme: LensTheme.dark(),
      home: const FilterPage(title: 'Food Stylists', vendorTypeSlug: 'food_stylist'),
    ));
    await tester.pumpAndSettle();

    expect(find.text('Filter Food Stylists'), findsOneWidget);
    expect(find.text('Category / Expertise'), findsOneWidget);
    expect(find.text('All Expertise'), findsOneWidget);
    expect(find.text('Recipe Development'), findsOneWidget);
    expect(find.text('Food Photography'), findsOneWidget);
    expect(find.text('Props Styling'), findsOneWidget);
    expect(find.text('Cuisine / Food Type'), findsOneWidget);
    expect(find.text('All Cuisines'), findsOneWidget);
    expect(find.text('Middle Eastern'), findsOneWidget);
    expect(find.text('Desserts & Bakery'), findsOneWidget);
    expect(find.text('Fine Dining'), findsOneWidget);
    expect(find.text('Egyptian Cities'), findsOneWidget);
    expect(find.text('Sharm El Sheikh'), findsOneWidget);
    expect(find.text('Beni Suef'), findsOneWidget);
    expect(find.text('Price Range (EGP / video)'), findsOneWidget);
    expect(find.text('Under 500'), findsOneWidget);
    expect(find.text('Content Type'), findsOneWidget);
    expect(find.text('Recipe Video'), findsOneWidget);
    expect(find.text('Product Shoot'), findsOneWidget);
    expect(find.text('Social Media Content'), findsOneWidget);
    expect(find.text('Apply Filters'), findsOneWidget);
  });
}

Map<String, dynamic> _bootstrap() {
  return {
    'tagline': 'Find. Book. Create.',
    'ai_prompt': 'What will you create today?',
    'ai_helper': 'Describe your idea and let AI find the right creatives for you.',
    'search_placeholder': 'Search photographers, studios, models...',
    'unread_notifications': 3,
    'features': {
      'ai_assistant': true,
      'bookings': true,
      'notifications': true,
      'filters': true,
      'badges': true,
      'reviews': true,
      'vendor_photographers': true,
      'favorites': true,
    },
    'vendor_types': [
      {'slug': 'photographer', 'label': 'Photographers'},
      {'slug': 'videographer', 'label': 'Videographers'},
      {'slug': 'studio', 'label': 'Studios'},
    ],
    'cities': [
      {'id': 1, 'name': 'Cairo'},
      {'id': 2, 'name': 'Giza'},
    ],
    'banks': [
      {'id': 'Banque Misr', 'label': 'Banque Misr'},
    ],
    'telecom_wallets': [
      {'id': 'vodafone', 'label': 'Vodafone Cash'},
    ],
    'popular': [
      {
        'slug': 'photographer',
        'title': 'Popular Photographer',
        'vendors': [
          {
            'id': 1,
            'display_name': 'Fahad Studio Light',
            'vendor_type': 'photographer',
            'vendor_type_name': 'Photographer',
            'city': 'Cairo',
            'location': 'Cairo, Egypt',
            'rating_avg': '4.90',
            'rating_count': '37',
            'badges': ['top-rated', 'verified'],
            'initials': 'FS',
            'tags': ['F&B', 'Events', 'Product'],
            'starting_from': 2500,
            'verified': true,
          },
        ],
      },
      {
        'slug': 'studio',
        'title': 'Featured Studios',
        'vendors': [
          {
            'id': 2,
            'display_name': 'Nile Loft Studio',
            'vendor_type': 'studio',
            'vendor_type_name': 'Studio',
            'city': 'Cairo',
            'location': 'Cairo, Egypt',
            'rating_avg': 4.7,
            'rating_count': 28,
            'badges': [],
            'initials': 'NL',
          },
        ],
      },
    ],
  };
}
