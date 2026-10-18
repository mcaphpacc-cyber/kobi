import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kobi_mobile/app/kobi_app.dart';

import 'support.dart';
import 'health_support.dart';

void main() {
  Future<void> mount(
    WidgetTester tester,
    Harness h, {
    Size size = const Size(390, 844),
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await h.session.restore();
    await tester.pumpWidget(
      KobiApp(session: h.session, environmentName: 'local'),
    );
    await tester.pumpAndSettle();
  }

  Future<void> finish(WidgetTester tester, Harness h) async {
    await tester.pumpWidget(const SizedBox());
    h.dispose();
  }

  Future<void> login(WidgetTester tester) async {
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Email'),
      'fixture@example.invalid',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Password'),
      'fixture password',
    );
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();
  }

  testWidgets(
    'login required validation and rejected credentials are visible',
    (tester) async {
      final h = Harness((_) async => denied(code: 'AUTHENTICATION_FAILED'));
      await mount(tester, h);
      await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
      await tester.pumpAndSettle();
      expect(find.text('Enter your email.'), findsOneWidget);
      expect(find.text('Enter your password.'), findsOneWidget);
      await login(tester);
      expect(
        find.text('Unable to sign in with these credentials.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
      await finish(tester, h);
    },
  );
  for (final size in [const Size(390, 844), const Size(768, 1024)]) {
    testWidgets('health shell Account and logout at $size', (tester) async {
      final h = Harness((request) async {
        if (request.url.path.endsWith('/health-profiles')) {
          return healthEnvelope([]);
        }
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/logout')) return ok({'revoked': true});
        return ok(identityData());
      });
      await mount(tester, h, size: size);
      await login(tester);
      expect(
        find.textContaining('No accessible health profiles.'),
        findsOneWidget,
      );
      await tester.tap(find.text('Account'));
      await tester.pumpAndSettle();
      expect(find.text('Email: fixture@example.invalid'), findsOneWidget);
      expect(find.text('Email verified: Yes'), findsOneWidget);
      expect(find.textContaining(credential('ka1', 0)), findsNothing);
      await tester.ensureVisible(find.widgetWithText(FilledButton, 'Logout'));
      await tester.tap(find.widgetWithText(FilledButton, 'Logout'));
      await tester.pumpAndSettle();
      expect(find.text('Welcome to KOBI'), findsOneWidget);
      expect(h.store.bundle, isNull);
      expect(find.text('Your KOBI account'), findsNothing);
      expect(tester.takeException(), isNull);
      await finish(tester, h);
    });
  }
  testWidgets('Logout All asks for password, validates and signs out', (
    tester,
  ) async {
    var all = 0;
    final h = Harness((request) async {
      if (request.url.path.endsWith('/health-profiles')) {
        return healthEnvelope([]);
      }
      if (request.url.path.endsWith('/login')) return ok(tokenData(0));
      if (request.url.path.endsWith('/logout-all')) {
        ++all;
        return ok({'revoked': true});
      }
      return ok(identityData());
    });
    await mount(tester, h);
    await login(tester);
    await tester.tap(find.text('Account'));
    await tester.pumpAndSettle();
    await tester.ensureVisible(
      find.widgetWithText(OutlinedButton, 'Logout All'),
    );
    await tester.tap(find.widgetWithText(OutlinedButton, 'Logout All'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('End all API sessions'));
    await tester.pumpAndSettle();
    expect(find.text('Enter your current password.'), findsOneWidget);
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Current password'),
      'fixture password',
    );
    await tester.tap(find.text('End all API sessions'));
    await tester.pumpAndSettle();
    expect(all, 1);
    expect(h.store.bundle, isNull);
    expect(find.text('Welcome to KOBI'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await finish(tester, h);
  });
  testWidgets('session expiry leaves Account and shows sign-in message', (
    tester,
  ) async {
    var expired = false;
    final h = Harness((request) async {
      if (request.url.path.endsWith('/health-profiles')) {
        return healthEnvelope([]);
      }
      if (request.url.path.endsWith('/login')) return ok(tokenData(0));
      return expired ? denied() : ok(identityData());
    });
    await mount(tester, h);
    await login(tester);
    await tester.tap(find.text('Account'));
    await tester.pumpAndSettle();
    expired = true;
    await tester.tap(find.text('Refresh account'));
    await tester.pumpAndSettle();
    expect(find.text('Welcome to KOBI'), findsOneWidget);
    expect(find.textContaining('Your session has ended.'), findsOneWidget);
    expect(find.text('Your KOBI account'), findsNothing);
    expect(h.store.bundle, isNull);
    expect(tester.takeException(), isNull);
    await finish(tester, h);
  });
  testWidgets('login remains scrollable with keyboard and large text', (
    tester,
  ) async {
    final h = Harness((_) async => denied(code: 'AUTHENTICATION_FAILED'));
    await mount(tester, h);
    tester.platformDispatcher.textScaleFactorTestValue = 1.6;
    tester.view.viewInsets = const FakeViewPadding(bottom: 330);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    addTearDown(tester.view.resetViewInsets);
    await tester.pumpAndSettle();
    await tester.showKeyboard(find.widgetWithText(TextFormField, 'Password'));
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Sign in'));
    expect(
      find.widgetWithText(FilledButton, 'Sign in').hitTestable(),
      findsOneWidget,
    );
    expect(tester.takeException(), isNull);
    await finish(tester, h);
  });
}
