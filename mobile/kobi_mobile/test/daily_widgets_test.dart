import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/app/kobi_app.dart';
import 'daily_support.dart';
import 'health_support.dart';

void main() {
  Future<DailyFixture> mount(
    WidgetTester tester, {
    DailyFixture? fixture,
    Size size = const Size(390, 844),
    double scale = 1,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    tester.platformDispatcher.textScaleFactorTestValue = scale;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    final f = fixture ?? DailyFixture();
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
    final target = find.text(text).last;
    await tester.ensureVisible(target);
    await tester.tap(target);
    await tester.pumpAndSettle();
  }

  Future<void> openCheck(WidgetTester tester) async {
    final row =
        find.widgetWithText(ListTile, 'Symptom Check').evaluate().isNotEmpty
        ? find.widgetWithText(ListTile, 'Symptom Check')
        : find.byType(ListTile).last;
    await tester.ensureVisible(row);
    await tester.tap(row);
    await tester.pumpAndSettle();
  }

  for (final size in [const Size(390, 844), const Size(768, 1024)]) {
    testWidgets('daily and saved journeys with real projections at $size', (
      tester,
    ) async {
      final f = await mount(tester, size: size);
      expect(find.text('Health Dashboard'), findsOneWidget);
      await tap(tester, 'Medication Schedule');
      expect(find.text('Fixture A'), findsOneWidget);
      expect(find.text('Active'), findsOneWidget);
      expect(find.text('Inactive'), findsOneWidget);
      expect(find.text('08:00'), findsNWidgets(2));
      expect(find.text('Asia/Kolkata'), findsNWidgets(2));
      expect(find.text('Starts: 18 Oct 2026'), findsNWidgets(2));
      expect(find.text('9007199254740993'), findsNothing);
      await tap(tester, 'Home');
      await tap(tester, 'Due Today');
      expect(find.text('No checkoff recorded'), findsOneWidget);
      expect(find.text('Taken'), findsOneWidget);
      expect(find.text('Skipped'), findsOneWidget);
      await tester.ensureVisible(find.text('23:59'));
      expect(find.text('America/New_York'), findsOneWidget);
      expect(find.text('Scheduled on: 17 Oct 2026'), findsOneWidget);
      expect(find.widgetWithText(FilledButton, 'Taken'), findsNothing);
      expect(find.widgetWithText(FilledButton, 'Skipped'), findsNothing);
      await tap(tester, 'Home');
      await tap(tester, 'Saved Symptom Checks');
      expect(find.text('Fixture A'), findsNothing);
      expect(
        find.textContaining('independent of the selected health profile'),
        findsOneWidget,
      );
      await openCheck(tester);
      expect(find.text('Historical snapshot'), findsOneWidget);
      expect(find.text('Historical cough'), findsOneWidget);
      expect(find.text('Total matches: 9'), findsOneWidget);
      expect(find.text('13% Symptom Match'), findsOneWidget);
      expect(find.text('99% Symptom Match'), findsOneWidget);
      expect(
        tester.getTopLeft(find.text('Historical result 90')).dy <
            tester.getTopLeft(find.text('Historical result 3')).dy,
        isTrue,
      );
      await tester.ensureVisible(find.text(disclaimer));
      expect(find.text(disclaimer), findsOneWidget);
      expect(find.textContaining('Clinical Confidence'), findsNothing);
      expect(find.text('Delete'), findsNothing);
      expect(find.text('Re-run'), findsNothing);
      await tester.tap(find.byType(BackButton));
      await tester.pumpAndSettle();
      expect(find.text('Saved Symptom Checks'), findsOneWidget);
      expect(
        f.paths.any(
          (p) => p.contains('symptom-checker') || p.contains('/diseases'),
        ),
        isFalse,
      );
      expect(tester.takeException(), isNull);
    });
  }
  for (final due in [false, true]) {
    testWidgets(
      'profile switch clears ${due ? 'due' : 'schedule'} and discards late A results',
      (tester) async {
        final f = DailyFixture()..profiles = [profileData(), profileData('3')];
        final delayed = Completer<http.Response>();
        final suffix = due ? '/due-today' : '/medication-schedules';
        f.respond = (r) async =>
            r.url.path.contains('/2/') && r.url.path.endsWith(suffix)
            ? delayed.future
            : null;
        await mount(tester, fixture: f);
        await tap(tester, 'Fixture A');
        final target = find.text(due ? 'Due Today' : 'Medication Schedule');
        await tester.ensureVisible(target);
        await tester.tap(target);
        await tester.pump();
        expect(find.byType(CircularProgressIndicator), findsOneWidget);
        await tap(tester, 'Change profile');
        await tap(tester, 'Fixture B');
        expect(find.text('Fixture A'), findsNothing);
        expect(find.text('Fixture B'), findsOneWidget);
        expect(find.text('08:00'), findsWidgets);
        delayed.complete(
          healthEnvelope([
            due
                ? {...dueData(), 'local_time': '01:23'}
                : {...slotData(), 'local_time': '01:23'},
          ], meta: pageMeta()),
        );
        await tester.pumpAndSettle();
        expect(find.text('01:23'), findsNothing);
        expect(find.text('08:00'), findsWidgets);
        expect(
          f.paths,
          contains(
            '/health-profiles/3/medication-schedules${due ? '/due-today' : ''}',
          ),
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'Saved remains accessible without profiles and after profile changes',
    (tester) async {
      final f = DailyFixture()..profiles = [];
      await mount(tester, fixture: f);
      await tap(tester, 'Saved');
      expect(find.textContaining('2 symptoms selected'), findsOneWidget);
      f.profiles = [profileData(), profileData('3')];
      await tap(tester, 'Health');
      await tap(tester, 'Refresh profiles');
      await tap(tester, 'Fixture A');
      await tap(tester, 'Change profile');
      await tap(tester, 'Fixture B');
      await tap(tester, 'Saved');
      expect(find.textContaining('2 symptoms selected'), findsOneWidget);
      expect(find.text('Fixture B'), findsNothing);
      expect(
        f.paths.any(
          (p) => p.contains('/health-profiles/') && p.contains('saved'),
        ),
        isFalse,
      );
    },
  );
  testWidgets('empty daily and saved screens explain exactly what is absent', (
    tester,
  ) async {
    final f = DailyFixture();
    f.respond = (r) async =>
        r.url.path.endsWith('/due-today') ||
            r.url.path.endsWith('/medication-schedules') ||
            r.url.path.endsWith('/saved-symptom-checks')
        ? healthEnvelope([], meta: pageMeta(total: 0))
        : null;
    await mount(tester, fixture: f);
    await tap(tester, 'Medication Schedule');
    expect(
      find.text(
        'No medication schedules have been recorded for this health profile.',
      ),
      findsOneWidget,
    );
    await tap(tester, 'Home');
    await tap(tester, 'Due Today');
    expect(find.textContaining('Nothing scheduled today.'), findsOneWidget);
    await tap(tester, 'Saved');
    expect(find.text('No saved symptom checks yet.'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'schedule error/retry, profile denial and missing saved detail are safe',
    (tester) async {
      final f = await mount(tester);
      f.respond = (r) async => r.url.path.endsWith('/medication-schedules')
          ? healthError(500)
          : null;
      await tap(tester, 'Medication Schedule');
      expect(
        find.text('The service is unavailable. Please try again later.'),
        findsOneWidget,
      );
      expect(find.textContaining('Private internal'), findsNothing);
      f.respond = null;
      await tap(tester, 'Retry');
      expect(find.text('Active'), findsOneWidget);
      f.respond = (r) async => r.url.path.endsWith('/medication-schedules')
          ? healthError(404)
          : null;
      await tap(tester, 'Refresh');
      expect(find.text('Choose a health profile'), findsOneWidget);
      await tap(tester, 'Saved');
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks/40')
          ? healthError(404)
          : null;
      await openCheck(tester);
      expect(
        find.text('This record is unavailable. Go back to the list or retry.'),
        findsOneWidget,
      );
      f.respond = null;
      await tap(tester, 'Retry');
      expect(find.text('Historical snapshot'), findsOneWidget);
    },
  );
  for (final resource in ['schedule', 'due', 'saved', 'detail']) {
    testWidgets(
      '$resource has deliberate loading and no previous record flashes',
      (tester) async {
        final f = await mount(tester);
        final delayed = Completer<http.Response>();
        final suffix = {
          'schedule': '/medication-schedules',
          'due': '/due-today',
          'saved': '/saved-symptom-checks',
          'detail': '/saved-symptom-checks/40',
        }[resource]!;
        f.respond = (r) async =>
            r.url.path.endsWith(suffix) ? delayed.future : null;
        if (resource == 'detail') {
          await tap(tester, 'Saved');
          await tester.tap(find.byType(ListTile).last);
        } else {
          final target = find.text(
            {
              'schedule': 'Medication Schedule',
              'due': 'Due Today',
              'saved': 'Saved',
            }[resource]!,
          );
          await tester.ensureVisible(target);
          await tester.tap(target);
        }
        await tester.pump();
        expect(find.byType(CircularProgressIndicator), findsOneWidget);
        delayed.complete(
          resource == 'detail'
              ? healthEnvelope(savedDetailData())
              : healthEnvelope([], meta: pageMeta(total: 0)),
        );
        await tester.pumpAndSettle();
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'schedule and saved paging append server pages and retain controls',
    (tester) async {
      final f = DailyFixture();
      f.respond = (r) async {
        if (!r.url.path.endsWith('/medication-schedules') &&
            !r.url.path.endsWith('/saved-symptom-checks')) {
          return null;
        }
        final second = r.url.queryParameters['page'] == '2',
            saved = r.url.path.endsWith('/saved-symptom-checks');
        return healthEnvelope([
          saved
              ? savedData(second ? '41' : '40')
              : slotData(second ? '31' : '30', !second),
        ], meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second));
      };
      await mount(tester, fixture: f);
      await tap(tester, 'Medication Schedule');
      await tap(tester, 'Load more');
      expect(find.text('Inactive'), findsOneWidget);
      expect(find.text('Load more'), findsNothing);
      await tap(tester, 'Saved');
      await tap(tester, 'Load more');
      expect(find.byType(ListTile), findsNWidgets(2));
    },
  );
  testWidgets(
    'long explanations, timezone labels and large text remain scrollable',
    (tester) async {
      final f = DailyFixture();
      f.respond = (r) async {
        if (r.url.path.endsWith('/saved-symptom-checks/40')) {
          final data = savedDetailData();
          final snapshot = data['snapshot'] as Map<String, Object?>;
          snapshot['results'] = [
            {
              ...resultData(),
              'label': 'Historical नाम <result> with a long descriptive label',
              'explanation': [
                List.filled(70, 'Long stored explanation.').join(' '),
              ],
            },
            resultData('3', 99),
          ];
          return healthEnvelope(data);
        }
        return null;
      };
      await mount(tester, fixture: f, scale: 1.6);
      await tap(tester, 'Medication Schedule');
      expect(tester.takeException(), isNull);
      await tap(tester, 'Home');
      await tap(tester, 'Due Today');
      await tester.ensureVisible(find.text('America/New_York'));
      expect(tester.takeException(), isNull);
      await tap(tester, 'Saved');
      await openCheck(tester);
      await tester.ensureVisible(find.text(disclaimer));
      expect(find.text(disclaimer).hitTestable(), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('logout removes account snapshot and private navigation', (
    tester,
  ) async {
    await mount(tester);
    await tap(tester, 'Saved');
    await openCheck(tester);
    await tap(tester, 'Account');
    await tap(tester, 'Logout');
    expect(find.text('Welcome to KOBI'), findsOneWidget);
    expect(find.text('Historical snapshot'), findsNothing);
    expect(find.byType(BackButton), findsNothing);
    expect(tester.takeException(), isNull);
  });
}
