import 'dart:async';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/health/health_models.dart';
import 'package:kobi_mobile/features/health/health_state.dart';
import 'health_support.dart';

void main() {
  Future<HealthFixture> fixture() async {
    final f = HealthFixture();
    await f.ready();
    addTearDown(f.dispose);
    return f;
  }

  Future<ProfileController> controller(HealthFixture f) async {
    final profiles = ProfileController(f.repository);
    addTearDown(profiles.dispose);
    await profiles.refresh();
    return profiles;
  }

  HealthScope scope(ProfileController profiles) {
    final value = HealthScope(profiles, profiles.selected!);
    addTearDown(value.dispose);
    return value;
  }

  test('one profile auto-selects only after authorized detail read', () async {
    final f = await fixture();
    final p = await controller(f);
    expect(p.selected?.id, '2');
    expect(f.paths, contains('/health-profiles/2'));
  });
  test(
    'multiple profiles require explicit selection without self inference',
    () async {
      final f = await fixture();
      f.profiles = [profileData(), profileData('3')];
      final p = await controller(f);
      expect(p.selected, isNull);
      await p.select(p.list.value!.last);
      expect(p.selected?.id, '3');
    },
  );
  test('no profiles is a distinct successful empty profile state', () async {
    final f = await fixture();
    f.profiles = [];
    final p = await controller(f);
    expect(p.selected, isNull);
    expect(p.list.value, isEmpty);
    expect(p.list.error, isNull);
    expect(f.paths.any((path) => path.contains('medical-summary')), isFalse);
  });
  test('profile selection error never installs an unchecked profile', () async {
    final f = await fixture();
    f.intercept = (r) async =>
        r.url.path.endsWith('/health-profiles/2') ? healthError(404) : null;
    final p = await controller(f);
    expect(p.selected, isNull);
    expect(p.list.value, isNull);
    expect(p.notice, isNotNull);
  });
  test(
    'summary success then error and explicit retry never fabricates zeros',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      await s.loadSummary();
      expect(s.summary.value?.counts['conditions'], 1);
      f.intercept = (r) async =>
          r.url.path.endsWith('/medical-summary') ? healthError(500) : null;
      await s.loadSummary();
      expect(s.summary.value, isNull);
      expect(s.summary.error?.status, 500);
      f.intercept = (r) async => r.url.path.endsWith('/medical-summary')
          ? healthEnvelope(summaryData('2', 0))
          : null;
      await s.loadSummary();
      expect(s.summary.error, isNull);
      expect(s.summary.value?.counts.values.every((v) => v == 0), isTrue);
    },
  );
  test('summary mismatched profile is rejected', () async {
    final f = await fixture();
    final p = await controller(f);
    final s = scope(p);
    f.intercept = (r) async => r.url.path.endsWith('/medical-summary')
        ? healthEnvelope(summaryData('3'))
        : null;
    await s.loadSummary();
    expect(s.summary.value, isNull);
    expect(s.summary.error?.kind, FailureKind.protocol);
  });
  test(
    'conditions use real pagination, preserve order, deduplicate and reset',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async {
        if (!r.url.path.endsWith('/conditions')) return null;
        expect(r.url.queryParameters['page_size'], '20');
        final next = r.url.queryParameters['page'] == '2';
        return healthEnvelope(
          next
              ? [conditionData('10'), conditionData('11')]
              : [conditionData('12'), conditionData('10')],
          meta: pageMeta(page: next ? 2 : 1, total: 21, more: !next),
        );
      };
      await s.conditions.load();
      expect(s.conditions.hasMore, isTrue);
      await s.conditions.load(more: true);
      expect(s.conditions.items.map((v) => v.id).join(','), '12,10,11');
      expect(s.conditions.hasMore, isFalse);
      await s.conditions.load();
      expect(s.conditions.page, 1);
      expect(s.conditions.items.length, 2);
    },
  );
  test(
    'condition pagination error keeps prior page and retries that next page',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      var fail = true;
      f.intercept = (r) async {
        if (!r.url.path.endsWith('/conditions')) return null;
        if (r.url.queryParameters['page'] == '2') {
          return fail
              ? healthError(503)
              : healthEnvelope([
                  conditionData('11'),
                ], meta: pageMeta(page: 2, total: 21));
        }
        return healthEnvelope([
          conditionData(),
        ], meta: pageMeta(total: 21, more: true));
      };
      await s.conditions.load();
      await s.conditions.load(more: true);
      expect(s.conditions.items.length, 1);
      expect(s.conditions.page, 1);
      expect(s.conditions.error?.status, 503);
      fail = false;
      await s.conditions.load(more: true);
      expect(s.conditions.items.length, 2);
      expect(s.conditions.page, 2);
    },
  );
  test(
    'empty and error conditions are distinct, detail reads actual projection',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async => r.url.path.endsWith('/conditions')
          ? healthEnvelope([], meta: pageMeta(total: 0))
          : null;
      await s.conditions.load();
      expect(s.conditions.initialized, isTrue);
      expect(s.conditions.items, isEmpty);
      expect(s.conditions.error, isNull);
      await s.loadCondition('10');
      expect(s.conditionDetail.value?.diagnosed?.display, '10 Jan 2026');
      f.intercept = (r) async =>
          r.url.path.endsWith('/conditions') ? healthError(500) : null;
      await s.conditions.load();
      expect(s.conditions.initialized, isFalse);
      expect(s.conditions.error?.status, 500);
    },
  );
  test(
    'medicines unpaginated list/detail/null dates, empty and error/retry',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async {
        if (r.url.path.endsWith('/medications')) {
          expect(r.url.hasQuery, isFalse);
        }
        return null;
      };
      await s.loadMedicines();
      expect(s.medicines.value?.length, 1);
      await s.loadMedicine('20');
      expect(s.medicineDetail.value?.started, isNull);
      expect(s.medicineDetail.value?.strength, '8 mg');
      f.intercept = (r) async =>
          r.url.path.endsWith('/medications') ? healthEnvelope([]) : null;
      await s.loadMedicines();
      expect(s.medicines.value, isEmpty);
      f.intercept = (r) async =>
          r.url.path.endsWith('/medications') ? healthError(500) : null;
      await s.loadMedicines();
      expect(s.medicines.value, isNull);
      expect(s.medicines.error?.status, 500);
      f.intercept = null;
      await s.loadMedicines();
      expect(s.medicines.value?.length, 1);
    },
  );
  for (final status in [403, 404]) {
    test(
      'profile-scoped $status invalidates selection and all old state owner',
      () async {
        final f = await fixture();
        final p = await controller(f);
        final s = scope(p);
        f.intercept = (r) async => r.url.path.endsWith('/medical-summary')
            ? healthError(status)
            : null;
        await s.loadSummary();
        expect(p.selected, isNull);
        expect(p.list.value, isNull);
      },
    );
  }
  test(
    'missing child remains resource error when parent is still accessible',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async =>
          r.url.path.endsWith('/conditions/10') ? healthError(404) : null;
      await s.loadCondition('10');
      await Future<void>.delayed(Duration.zero);
      expect(s.conditionDetail.value, isNull);
      expect(s.conditionDetail.error?.status, 404);
      expect(p.selected?.id, '2');
    },
  );
  test(
    'missing child plus denied parent recovers to profile selection',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async =>
          r.url.path.endsWith('/medications/20') ||
              r.url.path.endsWith('/health-profiles/2')
          ? healthError(404)
          : null;
      await s.loadMedicine('20');
      await Future<void>.delayed(Duration.zero);
      expect(p.selected, isNull);
    },
  );
  test(
    'profile A data is cleared and late A responses cannot overwrite B',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final a = HealthScope(p, p.selected!);
      await a.conditions.load();
      await a.loadMedicines();
      final delayed = Completer<http.Response>();
      f.intercept = (r) async =>
          r.url.path.endsWith('/2/medical-summary') ? delayed.future : null;
      final flight = a.loadSummary();
      await Future<void>.delayed(Duration.zero);
      p.choose();
      a.dispose();
      expect(a.conditions.items, isEmpty);
      expect(a.medicines.value, isNull);
      await p.select(HealthProfile.fromApi(profileData('3')));
      final b = scope(p);
      await b.loadSummary();
      delayed.complete(healthEnvelope(summaryData('2', 99)));
      await flight;
      expect(a.summary.value, isNull);
      expect(b.summary.value?.profile.id, '3');
      expect(b.summary.value?.counts['conditions'], 1);
    },
  );
  test('late profile validation cannot reinstall old selection', () async {
    final f = await fixture();
    final p = await controller(f);
    final delayed = Completer<http.Response>();
    f.intercept = (r) async =>
        r.url.path.endsWith('/health-profiles/2') ? delayed.future : null;
    final flight = p.select(HealthProfile.fromApi(profileData()));
    await Future<void>.delayed(Duration.zero);
    p.choose();
    await p.select(HealthProfile.fromApi(profileData('3')));
    delayed.complete(healthEnvelope(profileData()));
    await flight;
    expect(p.selected?.id, '3');
  });
  test('network failure has a safe distinct failure, not empty data', () async {
    final f = await fixture();
    final p = await controller(f);
    final s = scope(p);
    f.intercept = (r) async {
      if (r.url.path.endsWith('/medications')) {
        throw const SocketException('synthetic');
      }
      return null;
    };
    await s.loadMedicines();
    expect(s.medicines.error?.kind, FailureKind.network);
    expect(s.medicines.value, isNull);
  });
  test(
    '429 retains Retry-After and does not issue an immediate retry',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      var count = 0;
      f.intercept = (r) async {
        if (r.url.path.endsWith('/medical-summary')) {
          ++count;
          return healthError(429, retry: 30);
        }
        return null;
      };
      await s.loadSummary();
      await s.loadSummary();
      expect(count, 1);
      expect(s.summary.retryAllowed, isFalse);
      expect(s.summary.error?.retryAfter, 30);
    },
  );
  test(
    'health 401 shares existing refresh and repeated denial ends session',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async =>
          r.url.path.endsWith('/medical-summary') ? healthError(401) : null;
      await s.loadSummary();
      expect(f.paths.where((path) => path == '/auth/refresh').length, 1);
      expect(f.h.session.identity, isNull);
      expect(p.selected, isNull);
    },
  );
  for (final medicine in [false, true]) {
    test(
      'new ${medicine ? 'medicine' : 'condition'} detail supersedes an in-flight prior record',
      () async {
        final f = await fixture();
        final p = await controller(f);
        final s = scope(p);
        final delayed = Completer<http.Response>();
        final resource = medicine ? 'medications' : 'conditions';
        f.intercept = (r) async =>
            r.url.path.endsWith('/$resource/10') ? delayed.future : null;
        final old = medicine ? s.loadMedicine('10') : s.loadCondition('10');
        await Future<void>.delayed(Duration.zero);
        await (medicine ? s.loadMedicine('11') : s.loadCondition('11'));
        delayed.complete(
          healthEnvelope(medicine ? medicineData('10') : conditionData('10')),
        );
        await old;
        expect(
          medicine ? s.medicineDetail.value?.id : s.conditionDetail.value?.id,
          '11',
        );
      },
    );
  }
  test(
    'unexpected identity/detail and pagination envelopes are rejected',
    () async {
      final f = await fixture();
      final p = await controller(f);
      final s = scope(p);
      f.intercept = (r) async {
        if (r.url.path.endsWith('/conditions')) {
          return healthEnvelope([], meta: pageMeta(page: 2));
        }
        if (r.url.path.endsWith('/medications/20')) {
          return healthEnvelope(medicineData('21'));
        }
        return null;
      };
      await s.conditions.load();
      await s.loadMedicine('20');
      expect(s.conditions.error?.kind, FailureKind.protocol);
      expect(s.medicineDetail.error?.kind, FailureKind.protocol);
    },
  );
}
