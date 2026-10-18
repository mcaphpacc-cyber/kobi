import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/daily_health/daily_health_state.dart';
import 'package:kobi_mobile/features/health/health_models.dart';
import 'package:kobi_mobile/features/health/health_state.dart';
import 'checkoff_support.dart';
import 'daily_support.dart';
import 'health_support.dart';
import 'support.dart';

void main() {
  Future<(CheckoffFixture, DailyHealthScope)> setup({
    CheckoffFixture? fixture,
  }) async {
    final f = fixture ?? CheckoffFixture();
    await f.ready();
    final profiles = ProfileController(f.repository);
    await profiles.refresh();
    final health = HealthScope(profiles, profiles.selected!);
    final daily = DailyHealthScope(health);
    await daily.due.load();
    addTearDown(() {
      daily.dispose();
      health.dispose();
      profiles.dispose();
      f.dispose();
    });
    return (f, daily);
  }

  Map<String, Object?> confirmed(
    String? state, {
    String id = '30',
    String day = '2026-10-18',
  }) => {
    ...dueData(id, state),
    'health_medicine_id': '20',
    'scheduled_on': day,
  };
  for (final state in ['taken', 'skipped']) {
    test('exact $state POST contract and explicit response', () async {
      final (f, d) = await setup();
      final entry = d.due.items.first;
      await d.due.record(entry, state);
      final r = f.posts.single;
      expect(r.method, 'POST');
      expect(
        r.url.path,
        '/api/v1/health-profiles/2/medication-schedules/30/checkoff',
      );
      expect(r.url.query, isEmpty);
      expect(jsonDecode(r.body), {'state': state});
      expect(r.headers['Authorization'], startsWith('Bearer ka1_'));
      expect(r.headers['Content-Type'], 'application/json');
      expect(r.headers['Cookie'], isNull);
      expect(r.followRedirects, isFalse);
      expect(d.due.items.first.state, state);
      expect(d.due.pending(entry), isFalse);
      expect(d.due.items.first.scheduledOn.display, '18 Oct 2026');
    });
  }
  test('corrections in both directions use explicit states', () async {
    final (f, d) = await setup();
    await d.due.record(d.due.items[1], 'skipped');
    await d.due.record(d.due.items[1], 'taken');
    expect(f.posts.map((r) => jsonDecode(r.body)['state']), [
      'skipped',
      'taken',
    ]);
    expect(d.due.items[1].state, 'taken');
  });
  test('server resulting state wins over requested state', () async {
    final (f, d) = await setup();
    f.override = (r) async => r.url.path.endsWith('/checkoff')
        ? healthEnvelope(confirmed('skipped'))
        : null;
    await d.due.record(d.due.items.first, 'taken');
    expect(d.due.items.first.state, 'skipped');
  });
  for (final capability in [false, null, 'true']) {
    test(
      'false/missing/invalid capability fails closed: $capability',
      () async {
        final f = CheckoffFixture()
          ..capability = capability
          ..includeCapability = capability != null;
        final (_, d) = await setup(fixture: f);
        expect(d.due.canRecord, isFalse);
        if (d.due.items.isNotEmpty) {
          await d.due.record(d.due.items.first, 'taken');
        }
        expect(f.posts, isEmpty);
        if (capability == 'true') {
          expect(d.due.request.error?.kind, FailureKind.protocol);
        }
      },
    );
  }
  test('missing medication name never enables ambiguous actions', () async {
    final f = CheckoffFixture();
    f.override = (r) async =>
        r.url.path.endsWith('/medications') ? healthEnvelope([]) : null;
    final (_, d) = await setup(fixture: f);
    await d.due.record(d.due.items.first, 'taken');
    expect(f.posts, isEmpty);
    expect(d.due.canAct(d.due.items.first), isFalse);
  });
  test(
    'pending blocks only same entry and prevents duplicates, no optimistic state',
    () async {
      final (f, d) = await setup();
      final flight = Completer<http.Response>();
      f.override = (r) async =>
          r.url.path.endsWith('/30/checkoff') ? flight.future : null;
      final first = d.due.items.first;
      final one = d.due.record(first, 'taken');
      await Future<void>.delayed(Duration.zero);
      await d.due.record(first, 'skipped');
      expect(f.posts.length, 1);
      expect(d.due.items.first.state, isNull);
      expect(d.due.pending(first), isTrue);
      await d.due.record(d.due.items[1], 'skipped');
      expect(f.posts.length, 2);
      expect(d.due.items[1].state, 'skipped');
      flight.complete(healthEnvelope(confirmed('taken')));
      await one;
      expect(d.due.items.first.state, 'taken');
    },
  );
  for (final status in [403, 404, 409, 422, 429, 500]) {
    test(
      'safe $status error retains state and never retries automatically',
      () async {
        final (f, d) = await setup();
        f.override = (r) async =>
            r.url.path.endsWith('/checkoff') ? healthError(status) : null;
        final old = d.due.items[1];
        await d.due.record(old, 'skipped');
        expect(d.due.items[1].state, 'taken');
        expect(f.posts.length, 1);
        expect(d.due.actionError(old), isNotNull);
        expect(d.due.actionError(old), isNot(contains('Private internal')));
        expect(d.due.pending(old), isFalse);
        await d.due.record(old, 'skipped');
        expect(f.posts.length, 1);
      },
    );
  }
  for (final failure in [
    'network',
    'timeout',
    'malformed',
    'wrong-id',
    'null-state',
  ]) {
    test(
      'uncertain $failure retains confirmed data and requires refresh',
      () async {
        final (f, d) = await setup();
        f.override = (r) async {
          if (!r.url.path.endsWith('/checkoff')) return null;
          if (failure == 'network') {
            throw const SocketException('Private network details');
          }
          if (failure == 'timeout') {
            throw const ApiFailure(
              FailureKind.timeout,
              'The request timed out. Please try again.',
            );
          }
          return healthEnvelope(
            failure == 'malformed'
                ? {}
                : confirmed(
                    failure == 'null-state' ? null : 'skipped',
                    id: failure == 'wrong-id' ? '999' : '31',
                  ),
          );
        };
        await d.due.record(d.due.items[1], 'skipped');
        expect(d.due.items[1].state, 'taken');
        expect(f.posts.length, 1);
        expect(d.due.canAct(d.due.items[1]), isFalse);
        f.override = null;
        await d.due.load();
        expect(d.due.canAct(d.due.items[1]), isTrue);
      },
    );
  }
  test(
    'GET begun before POST cannot overwrite confirmed result or permission',
    () async {
      final (f, d) = await setup();
      final get = Completer<http.Response>();
      f.override = (r) async =>
          r.url.path.endsWith('/due-today') ? get.future : null;
      // An append read retains visible entries while in flight.
      d.due.hasMore = true;
      final reading = d.due.load(more: true);
      await Future<void>.delayed(Duration.zero);
      await d.due.record(d.due.items.first, 'taken');
      get.complete(
        healthEnvelope(
          [confirmed(null)],
          meta: {...pageMeta(page: 2), 'can_record_checkoffs': false},
        ),
      );
      await reading;
      expect(d.due.items.first.state, 'taken');
      expect(d.due.canRecord, isTrue);
    },
  );
  test('refresh during pending write does not start a competing GET', () async {
    final (f, d) = await setup();
    final post = Completer<http.Response>();
    f.override = (r) async =>
        r.url.path.endsWith('/checkoff') ? post.future : null;
    final flight = d.due.record(d.due.items.first, 'taken');
    await Future<void>.delayed(Duration.zero);
    final before = f.requests.length;
    await d.due.load();
    expect(f.requests.length, before);
    post.complete(healthEnvelope(confirmed('taken')));
    await flight;
  });
  test('disposed profile A POST cannot repopulate profile B', () async {
    final (f, a) = await setup();
    final post = Completer<http.Response>();
    f.override = (r) async =>
        r.url.path.endsWith('/checkoff') ? post.future : null;
    final flight = a.due.record(a.due.items.first, 'taken');
    await Future<void>.delayed(Duration.zero);
    // Dispose only old daily scope here; setup teardown remains responsible for its other state.
    a.due.dispose();
    final profiles = ProfileController(f.repository);
    await profiles.refresh();
    await profiles.select(HealthProfile.fromApi(profileData('3')));
    final health = HealthScope(profiles, profiles.selected!);
    final b = DailyHealthScope(health);
    await b.due.load();
    addTearDown(() {
      b.dispose();
      health.dispose();
      profiles.dispose();
    });
    post.complete(healthEnvelope(confirmed('taken')));
    await flight;
    expect(b.due.items.first.state, isNull);
  });
  test(
    'server date rollover displays returned day, removes stale list and requires refresh',
    () async {
      final (f, d) = await setup();
      f.override = (r) async => r.url.path.endsWith('/checkoff')
          ? healthEnvelope(confirmed('taken', day: '2026-10-19'))
          : null;
      await d.due.record(d.due.items.first, 'taken');
      expect(d.due.items.length, 1);
      expect(d.due.items.first.scheduledOn.display, '19 Oct 2026');
      expect(d.due.canRecord, isFalse);
      expect(d.due.notices['30'], contains('date changed'));
    },
  );
  test(
    'two in-flight rollover responses retain both server confirmations',
    () async {
      final (f, d) = await setup();
      final a = Completer<http.Response>(), b = Completer<http.Response>();
      f.override = (r) async => r.url.path.endsWith('/30/checkoff')
          ? a.future
          : r.url.path.endsWith('/31/checkoff')
          ? b.future
          : null;
      final one = d.due.record(d.due.items[0], 'taken');
      final two = d.due.record(d.due.items[1], 'skipped');
      await Future<void>.delayed(Duration.zero);
      b.complete(
        healthEnvelope(confirmed('skipped', id: '31', day: '2026-10-19')),
      );
      await two;
      a.complete(healthEnvelope(confirmed('taken', day: '2026-10-19')));
      await one;
      expect(
        {for (final entry in d.due.items) entry.id: entry.state},
        {'30': 'taken', '31': 'skipped'},
      );
      expect(
        d.due.items.every((e) => e.scheduledOn.display == '19 Oct 2026'),
        isTrue,
      );
      expect(d.due.canRecord, isFalse);
    },
  );
  test('expired access uses existing preflight refresh once', () async {
    final (f, d) = await setup();
    f.h.time = f.h.time.add(const Duration(minutes: 11));
    await d.due.record(d.due.items.first, 'taken');
    expect(
      f.requests.where((r) => r.url.path.endsWith('/auth/refresh')).length,
      1,
    );
    expect(
      f.posts.single.headers['Authorization'],
      'Bearer ${credential('ka1', 1)}',
    );
    expect(d.due.items.first.state, 'taken');
  });
  test(
    'definite admission 401 uses existing coordinator once; no network replay',
    () async {
      final (f, d) = await setup();
      f.override = (r) async =>
          r.url.path.endsWith('/checkoff') && f.posts.length == 1
          ? healthError(401)
          : null;
      await d.due.record(d.due.items.first, 'taken');
      expect(f.posts.length, 2);
      expect(
        f.requests.where((r) => r.url.path.endsWith('/auth/refresh')).length,
        1,
      );
      expect(d.due.items.first.state, 'taken');
    },
  );
  test('failed refresh terminates session and sends no write', () async {
    final (f, d) = await setup();
    f.h.time = f.h.time.add(const Duration(minutes: 11));
    f.override = (r) async =>
        r.url.path.endsWith('/auth/refresh') ? healthError(401) : null;
    await d.due.record(d.due.items.first, 'taken');
    expect(f.h.session.authenticated, isFalse);
    expect(f.posts, isEmpty);
  });
  test(
    'transport permits only narrow private checkoff POST and exact fields',
    () async {
      final (f, _) = await setup();
      for (final path in [
        '/health-profiles/2/medication-schedules/30/update',
        '/health-profiles/2/medication-schedules/30/checkoff/extra',
        '/diseases/checkoff',
      ]) {
        await expectLater(
          f.h.api.request(
            'POST',
            path,
            accessToken: 'access',
            body: {'state': 'taken'},
          ),
          throwsA(isA<ApiFailure>()),
        );
      }
      await expectLater(
        f.h.api.request(
          'POST',
          '/health-profiles/2/medication-schedules/30/checkoff',
          accessToken: 'access',
          body: {'state': 'taken', 'scheduled_on': '2026-10-18'},
        ),
        throwsA(isA<ApiFailure>()),
      );
      expect(f.posts, isEmpty);
    },
  );
}
