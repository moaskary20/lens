import 'package:flutter_test/flutter_test.dart';
import 'package:lens/core/config.dart';
import 'package:lens/core/session_store.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  test('restores signed-in account after app restart', () async {
    SharedPreferences.setMockInitialValues({});
    LensConfig.useNetwork = false;
    await SessionStore.instance.reset();

    await SessionStore.instance.login(
      email: 'client@lens.app',
      password: 'password',
    );
    expect(SessionStore.instance.email, 'client@lens.app');
    expect(SessionStore.instance.shouldOpenHome, isTrue);

    // Cold start: memory wiped, disk kept.
    SessionStore.instance.clearMemoryOnly();
    expect(SessionStore.instance.isGuest, isTrue);

    await SessionStore.instance.restore();
    expect(SessionStore.instance.email, 'client@lens.app');
    expect(SessionStore.instance.isClient, isTrue);
    expect(SessionStore.instance.shouldOpenHome, isTrue);
  });

  test('sign out clears persisted session', () async {
    SharedPreferences.setMockInitialValues({});
    LensConfig.useNetwork = false;
    await SessionStore.instance.reset();

    await SessionStore.instance.login(
      email: 'client@lens.app',
      password: 'password',
    );
    await SessionStore.instance.signOut();

    SessionStore.instance.clearMemoryOnly();
    await SessionStore.instance.restore();
    expect(SessionStore.instance.isGuest, isTrue);
  });
}
