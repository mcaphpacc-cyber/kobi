import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/app/kobi_app.dart';
import 'health_support.dart';

void main() {
  Future<HealthFixture> mount(
    WidgetTester tester, {
    Size size = const Size(390, 844),
    HealthFixture? fixture,
    double textScale = 1,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    tester.platformDispatcher.textScaleFactorTestValue = textScale;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    final f = fixture ?? HealthFixture();
    await f.ready();
    await tester.pumpWidget(
      KobiApp(session: f.h.session, environmentName: 'local'),
    );
    await tester.pumpAndSettle();
    addTearDown(() async {
      await tester.pumpWidget(const SizedBox());
      f.dispose();
    });
    return f;
  }

  Future<void> tap(WidgetTester tester, String text) async {
    final finder = find.text(text).last;
    await tester.ensureVisible(finder);
    await tester.tap(finder);
    await tester.pumpAndSettle();
  }

  Future<void> back(WidgetTester tester) async {
    await tester.tap(find.byType(BackButton));
    await tester.pumpAndSettle();
  }

  for (final size in [const Size(390, 844), const Size(768, 1024)]) {
    testWidgets(
      'dashboard, profile, summary, condition and medicine navigation $size',
      (tester) async {
        await mount(tester, size: size);
        expect(find.text('Health Dashboard'), findsOneWidget);
        expect(find.text('Fixture A'), findsOneWidget);
        expect(find.text('Active / chronic conditions: 1'), findsOneWidget);
        expect(find.text('Allergies: 0'), findsOneWidget);
        await tap(tester, 'Medical Summary');
        expect(
          find.text(
            'Recorded counts only. This is not a complete clinical report.',
          ),
          findsOneWidget,
        );
        await tap(tester, 'Active / chronic conditions: 1');
        expect(find.text('Fixture condition 10'), findsOneWidget);
        expect(find.textContaining('Diagnosed: 10 Jan 2026'), findsOneWidget);
        await tap(tester, 'Fixture condition 10');
        expect(find.text('Condition Detail'), findsOneWidget);
        expect(find.text('10 Jan 2026'), findsOneWidget);
        await back(tester);
        expect(find.text('Conditions'), findsOneWidget);
        await back(tester);
        expect(find.text('Medical Summary'), findsOneWidget);
        await tap(tester, 'Current medicines: 1');
        expect(find.text('Fixture medicine 20'), findsOneWidget);
        await tap(tester, 'Fixture medicine 20');
        expect(find.text('Medicine Detail'), findsOneWidget);
        await tester.ensureVisible(find.text('Stopped on'));
        expect(find.text('Not recorded'), findsWidgets);
        expect(find.text('8 mg'), findsOneWidget);
        await tap(tester, 'Home');
        await tap(tester, 'Health Profile');
        expect(find.text('Name'), findsOneWidget);
        expect(find.text('Profile type'), findsOneWidget);
        await tap(tester, 'Home');
        await tap(tester, 'Conditions');
        expect(find.text('Fixture condition 10'), findsOneWidget);
        await tap(tester, 'Home');
        await tap(tester, 'Personal Medicines');
        expect(find.text('Fixture medicine 20'), findsOneWidget);
        expect(find.textContaining('clinical confidence'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'multiple profile selector and switch erase previous screens and data',
    (tester) async {
      final f = HealthFixture()..profiles = [profileData(), profileData('3')];
      await mount(tester, fixture: f);
      expect(find.text('Choose a health profile'), findsOneWidget);
      await tap(tester, 'Fixture A');
      await tap(tester, 'Conditions');
      await tap(tester, 'Fixture condition 10');
      await tap(tester, 'Change profile');
      expect(find.text('Condition Detail'), findsNothing);
      expect(find.text('Fixture condition 10'), findsNothing);
      await tap(tester, 'Fixture B');
      expect(find.text('Fixture A'), findsNothing);
      // Banner plus the Health Profile name field show the newly selected person.
      expect(find.text('Fixture B'), findsNWidgets(2));
      expect(find.byType(BackButton), findsNothing);
      await tap(tester, 'Personal Medicines');
      expect(f.paths, contains('/health-profiles/3/medications'));
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'late profile A list response cannot reappear after selecting B',
    (tester) async {
      final f = HealthFixture()..profiles = [profileData(), profileData('3')];
      final delayed = Completer<http.Response>();
      f.intercept = (r) async =>
          r.url.path.endsWith('/2/medications') ? delayed.future : null;
      await mount(tester, fixture: f);
      await tap(tester, 'Fixture A');
      await tester.ensureVisible(find.text('Personal Medicines'));
      await tester.tap(find.text('Personal Medicines'));
      await tester.pump();
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      await tap(tester, 'Change profile');
      await tap(tester, 'Fixture B');
      await tap(tester, 'Personal Medicines');
      expect(find.text('Fixture medicine 20'), findsOneWidget);
      delayed.complete(healthEnvelope([medicineData('98')]));
      await tester.pumpAndSettle();
      expect(find.text('Fixture medicine 98'), findsNothing);
      expect(find.text('Fixture medicine 20'), findsOneWidget);
      expect(find.text('Fixture A'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'no profiles is not represented as empty medical records; refresh discovers one',
    (tester) async {
      final f = HealthFixture()..profiles = [];
      await mount(tester, fixture: f);
      expect(
        find.textContaining('No accessible health profiles.'),
        findsOneWidget,
      );
      expect(find.text('Allergies: 0'), findsNothing);
      f.profiles = [profileData()];
      await tap(tester, 'Refresh profiles');
      expect(find.text('Health Dashboard'), findsOneWidget);
    },
  );
  testWidgets(
    'empty condition/medicine lists and summary zeros are deliberate states',
    (tester) async {
      final f = HealthFixture();
      f.intercept = (r) async {
        if (r.url.path.endsWith('/conditions')) {
          return healthEnvelope([], meta: pageMeta(total: 0));
        }
        if (r.url.path.endsWith('/medications')) return healthEnvelope([]);
        if (r.url.path.endsWith('/medical-summary')) {
          return healthEnvelope(summaryData('2', 0));
        }
        return null;
      };
      await mount(tester, fixture: f);
      expect(find.text('Active / chronic conditions: 0'), findsOneWidget);
      await tap(tester, 'Conditions');
      expect(
        find.text('No conditions recorded for this profile.'),
        findsOneWidget,
      );
      await tap(tester, 'Home');
      await tap(tester, 'Personal Medicines');
      expect(
        find.text('No medicines recorded for this profile.'),
        findsOneWidget,
      );
    },
  );
  testWidgets('safe summary error, retry and loading replace previous data', (
    tester,
  ) async {
    final f = HealthFixture();
    var fail = true;
    f.intercept = (r) async => r.url.path.endsWith('/medical-summary') && fail
        ? healthError(500)
        : null;
    await mount(tester, fixture: f);
    expect(
      find.text('The service is unavailable. Please try again later.'),
      findsOneWidget,
    );
    expect(find.textContaining('Private internal'), findsNothing);
    expect(find.text('Active / chronic conditions: 0'), findsNothing);
    fail = false;
    await tap(tester, 'Retry');
    expect(find.text('Active / chronic conditions: 1'), findsOneWidget);
    final delayed = Completer<http.Response>();
    f.intercept = (r) async =>
        r.url.path.endsWith('/medical-summary') ? delayed.future : null;
    await tester.ensureVisible(find.text('Refresh summary'));
    await tester.tap(find.text('Refresh summary'));
    await tester.pump();
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    expect(find.text('Active / chronic conditions: 1'), findsNothing);
    delayed.complete(healthEnvelope(summaryData()));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });
  testWidgets('profile access loss clears dashboard and recovers selector', (
    tester,
  ) async {
    final f = await mount(tester);
    f.intercept = (r) async =>
        r.url.path.endsWith('/conditions') ? healthError(404) : null;
    await tap(tester, 'Conditions');
    expect(find.text('Choose a health profile'), findsOneWidget);
    expect(find.text('Fixture A'), findsNothing);
    expect(find.textContaining('no longer available'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'missing child displays error without discarding accessible profile',
    (tester) async {
      final f = await mount(tester);
      f.intercept = (r) async =>
          r.url.path.endsWith('/conditions/10') ? healthError(404) : null;
      await tap(tester, 'Conditions');
      await tap(tester, 'Fixture condition 10');
      expect(
        find.text('This record is unavailable. Go back to the list or retry.'),
        findsOneWidget,
      );
      expect(find.text('Fixture A'), findsOneWidget);
      await back(tester);
      expect(find.text('Fixture condition 10'), findsOneWidget);
    },
  );
  testWidgets('conditions Load more appends server page without resorting', (
    tester,
  ) async {
    final f = HealthFixture();
    f.intercept = (r) async {
      if (!r.url.path.endsWith('/conditions')) return null;
      final next = r.url.queryParameters['page'] == '2';
      return healthEnvelope([
        conditionData(next ? '11' : '10'),
      ], meta: pageMeta(page: next ? 2 : 1, total: 21, more: !next));
    };
    await mount(tester, fixture: f);
    await tap(tester, 'Conditions');
    await tap(tester, 'Load more');
    expect(find.text('Fixture condition 10'), findsOneWidget);
    expect(find.text('Fixture condition 11'), findsOneWidget);
    expect(find.text('Load more'), findsNothing);
  });
  testWidgets('large text and long names stay scrollable across core screens', (
    tester,
  ) async {
    final f = HealthFixture();
    f.intercept = (r) async => r.url.path.endsWith('/health-profiles/2')
        ? healthEnvelope({
            ...profileData(),
            'full_name':
                'A long fixture profile name with multiple words for readable wrapping',
          })
        : null;
    await mount(tester, fixture: f, textScale: 1.6);
    for (final destination in [
      'Medical Summary',
      'Conditions',
      'Personal Medicines',
      'Health Profile',
    ]) {
      await tap(tester, destination);
      expect(tester.takeException(), isNull);
      await tap(tester, 'Home');
    }
    await tap(tester, 'Account');
    expect(find.text('Your KOBI account'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('medicine list and detail errors remain safe and retryable', (
    tester,
  ) async {
    final f = await mount(tester);
    f.intercept = (r) async =>
        r.url.path.endsWith('/medications') ? healthError(503) : null;
    await tap(tester, 'Personal Medicines');
    expect(
      find.text('The service is unavailable. Please try again later.'),
      findsOneWidget,
    );
    expect(find.textContaining('No medicines recorded'), findsNothing);
    f.intercept = null;
    await tap(tester, 'Retry');
    f.intercept = (r) async =>
        r.url.path.endsWith('/medications/20') ? healthError(404) : null;
    await tap(tester, 'Fixture medicine 20');
    expect(
      find.text('This record is unavailable. Go back to the list or retry.'),
      findsOneWidget,
    );
    expect(find.text('Fixture A'), findsOneWidget);
    f.intercept = null;
    await tap(tester, 'Retry');
    expect(find.text('8 mg'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('logout removes health scope and old private back stack', (
    tester,
  ) async {
    await mount(tester);
    await tap(tester, 'Personal Medicines');
    await tap(tester, 'Fixture medicine 20');
    await tap(tester, 'Account');
    await tap(tester, 'Logout');
    expect(find.text('Welcome to KOBI'), findsOneWidget);
    expect(find.text('Fixture A'), findsNothing);
    expect(find.text('Medicine Detail'), findsNothing);
    expect(find.byType(BackButton), findsNothing);
  });
}
