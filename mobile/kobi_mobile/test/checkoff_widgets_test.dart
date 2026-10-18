import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/app/kobi_app.dart';
import 'package:kobi_mobile/features/daily_health/daily_health_screen.dart';
import 'package:kobi_mobile/features/daily_health/daily_health_state.dart';
import 'package:kobi_mobile/features/health/health_state.dart';
import 'checkoff_support.dart';
import 'daily_support.dart';
import 'health_support.dart';

void main() {
  Future<(CheckoffFixture, DailyHealthScope)> mount(
    WidgetTester tester, {
    CheckoffFixture? fixture,
    Size size = const Size(390, 844),
    double scale = 1,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    tester.platformDispatcher.textScaleFactorTestValue = scale;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    final f = fixture ?? CheckoffFixture();
    await f.ready();
    final profiles = ProfileController(f.repository);
    await profiles.refresh();
    final health = HealthScope(profiles, profiles.selected!);
    final daily = DailyHealthScope(health);
    await daily.due.load();
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          appBar: AppBar(title: const Text('Fixture A')),
          body: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: DailyHealthScreen(scope: daily, dueToday: true),
            ),
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();
    addTearDown(() async {
      await tester.pumpWidget(const SizedBox());
      daily.dispose();
      health.dispose();
      profiles.dispose();
      f.dispose();
    });
    return (f, daily);
  }

  Finder button(String id, String state) => find.byKey(ValueKey('$id-$state'));
  Future<void> press(WidgetTester tester, Finder finder) async {
    await tester.ensureVisible(finder);
    await tester.tap(finder);
    await tester.pumpAndSettle();
  }

  for (final size in [const Size(390, 844), const Size(768, 1024)]) {
    testWidgets('Taken/Skipped/correction and readable server state at $size', (
      tester,
    ) async {
      final (f, d) = await mount(tester, size: size);
      expect(find.text('Recorded test medicine'), findsNWidgets(3));
      expect(find.text('No checkoff recorded'), findsOneWidget);
      await press(tester, button('30', 'taken'));
      expect(d.due.items.first.state, 'taken');
      expect(button('30', 'taken'), findsNothing);
      expect(button('30', 'skipped'), findsOneWidget);
      await press(tester, button('30', 'skipped'));
      expect(d.due.items.first.state, 'skipped');
      expect(button('30', 'taken'), findsOneWidget);
      await press(tester, button('31', 'skipped'));
      expect(d.due.items[1].state, 'skipped');
      await press(tester, button('32', 'taken'));
      expect(d.due.items[2].state, 'taken');
      expect(f.posts.length, 4);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'pending progress, duplicate tap disabled and other entry enabled',
    (tester) async {
      final (f, d) = await mount(tester);
      final response = Completer<http.Response>();
      f.override = (r) async =>
          r.url.path.endsWith('/30/checkoff') ? response.future : null;
      await tester.ensureVisible(button('30', 'taken'));
      await tester.tap(button('30', 'taken'));
      await tester.pump();
      expect(find.text('Recording medication action…'), findsOneWidget);
      expect(d.due.items.first.state, isNull);
      expect(
        tester.widget<FilledButton>(button('30', 'taken')).onPressed,
        isNull,
      );
      expect(
        tester.widget<FilledButton>(button('31', 'skipped')).onPressed,
        isNotNull,
      );
      expect(f.posts.length, 1);
      response.complete(
        healthEnvelope({...dueData('30', 'taken'), 'health_medicine_id': '20'}),
      );
      await tester.pumpAndSettle();
      expect(d.due.items.first.state, 'taken');
      expect(find.text('Recording medication action…'), findsNothing);
    },
  );
  for (final include in [true, false]) {
    testWidgets(
      '${include ? 'false' : 'missing'} capability keeps cards read only',
      (tester) async {
        final f = CheckoffFixture()
          ..capability = false
          ..includeCapability = include;
        await mount(tester, fixture: f);
        expect(find.text('No checkoff recorded'), findsOneWidget);
        expect(button('30', 'taken'), findsNothing);
        expect(button('31', 'skipped'), findsNothing);
        expect(f.posts, isEmpty);
      },
    );
  }
  testWidgets(
    'error retains Taken and displays only safe message; refresh recovers',
    (tester) async {
      final (f, d) = await mount(tester);
      f.override = (r) async =>
          r.url.path.endsWith('/checkoff') ? healthError(409) : null;
      await press(tester, button('31', 'skipped'));
      expect(d.due.items[1].state, 'taken');
      expect(find.textContaining('no longer available'), findsOneWidget);
      expect(find.textContaining('Private internal'), findsNothing);
      f.override = null;
      await press(tester, find.text('Refresh'));
      expect(d.due.canAct(d.due.items[1]), isTrue);
    },
  );
  testWidgets('medication name lookup failure is read only and can recover', (
    tester,
  ) async {
    final f = CheckoffFixture();
    f.override = (r) async =>
        r.url.path.endsWith('/medications') ? healthError(500) : null;
    await mount(tester, fixture: f);
    expect(button('30', 'taken'), findsNothing);
    expect(
      find.textContaining('Medication details unavailable'),
      findsNWidgets(3),
    );
    f.override = null;
    await press(tester, find.text('Refresh medication details').first);
    expect(button('30', 'taken'), findsOneWidget);
    expect(f.posts, isEmpty);
  });
  testWidgets(
    'large text long names multiple entries keep controls within viewport',
    (tester) async {
      final f = CheckoffFixture()
        ..name = List.filled(10, 'Long recorded medication name').join(' ');
      await mount(tester, fixture: f, scale: 1.8);
      for (final target in [
        button('30', 'taken'),
        button('30', 'skipped'),
        button('31', 'skipped'),
        button('32', 'taken'),
      ]) {
        await tester.ensureVisible(target);
        await tester.pumpAndSettle();
        final rect = tester.getRect(target);
        expect(rect.left, greaterThanOrEqualTo(0));
        expect(rect.right, lessThanOrEqualTo(390));
      }
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('action/state/progress have meaningful semantics', (
    tester,
  ) async {
    await mount(tester);
    final semantics = tester.ensureSemantics();
    try {
      await tester.ensureVisible(find.text('Taken'));
      await tester.pumpAndSettle();
      expect(
        find.bySemanticsLabel(RegExp('Current recorded state: Taken')),
        findsOneWidget,
      );
      await tester.ensureVisible(button('30', 'taken'));
      await tester.pumpAndSettle();
      expect(
        find.bySemanticsLabel(
          RegExp('Record Taken for Recorded test medicine'),
        ),
        findsOneWidget,
      );
      await tester.ensureVisible(button('31', 'skipped'));
      await tester.pumpAndSettle();
      expect(
        find.bySemanticsLabel(
          RegExp('Change to Skipped for Recorded test medicine'),
        ),
        findsOneWidget,
      );
    } finally {
      semantics.dispose();
    }
  });
  testWidgets(
    'real profile switch discards pending A POST before rendering B',
    (tester) async {
      final f = CheckoffFixture()..profiles = [profileData(), profileData('3')];
      final pending = Completer<http.Response>();
      f.override = (r) async =>
          r.url.path.contains('/2/') && r.url.path.endsWith('/checkoff')
          ? pending.future
          : null;
      await f.ready();
      await tester.pumpWidget(
        KobiApp(session: f.h.session, environmentName: 'local'),
      );
      await tester.pumpAndSettle();
      addTearDown(() async {
        await tester.pumpWidget(const SizedBox());
        f.dispose();
      });
      await press(tester, find.text('Fixture A'));
      await press(tester, find.text('Due Today'));
      await tester.ensureVisible(button('30', 'taken'));
      await tester.tap(button('30', 'taken'));
      await tester.pump();
      await press(tester, find.text('Change profile'));
      await press(tester, find.text('Fixture B'));
      pending.complete(
        healthEnvelope({...dueData('30', 'taken'), 'health_medicine_id': '20'}),
      );
      await tester.pumpAndSettle();
      expect(find.text('Fixture A'), findsNothing);
      expect(find.text('Fixture B'), findsOneWidget);
      expect(find.text('No checkoff recorded'), findsOneWidget);
      expect(button('30', 'taken'), findsOneWidget);
      expect(f.posts.single.url.path, contains('/2/'));
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'full authenticated shell reaches actions and Schedule stays read only',
    (tester) async {
      tester.view.devicePixelRatio = 1;
      tester.view.physicalSize = const Size(390, 844);
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final f = CheckoffFixture();
      await f.ready();
      await tester.pumpWidget(
        KobiApp(session: f.h.session, environmentName: 'local'),
      );
      await tester.pumpAndSettle();
      addTearDown(() async {
        await tester.pumpWidget(const SizedBox());
        f.dispose();
      });
      await press(tester, find.text('Due Today'));
      expect(button('30', 'taken'), findsOneWidget);
      await press(tester, button('30', 'taken'));
      expect(find.text('Recorded by server.'), findsOneWidget);
      await press(tester, find.text('Home'));
      await press(tester, find.text('Medication Schedule'));
      expect(find.text('Active'), findsOneWidget);
      expect(find.textContaining('Record Taken'), findsNothing);
      expect(f.posts.length, 1);
    },
  );
}
