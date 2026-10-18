import 'dart:async';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/daily_health/daily_health_state.dart';
import 'package:kobi_mobile/features/health/health_state.dart';
import 'package:kobi_mobile/features/health/health_models.dart';
import 'package:kobi_mobile/features/saved_checks/saved_state.dart';
import 'daily_support.dart';
import 'health_support.dart';

void main() {
  Future<DailyFixture> fixture() async {
    final f = DailyFixture();
    await f.ready();
    addTearDown(f.dispose);
    return f;
  }

  Future<(ProfileController, HealthScope, DailyHealthScope)> scoped(
    DailyFixture f,
  ) async {
    final profiles = ProfileController(f.repository);
    await profiles.refresh();
    final health = HealthScope(profiles, profiles.selected!);
    final daily = DailyHealthScope(health);
    addTearDown(() {
      daily.dispose();
      health.dispose();
      profiles.dispose();
    });
    return (profiles, health, daily);
  }

  SavedChecksController saved(DailyFixture f) {
    final value = SavedChecksController(SavedCheckRepository(f.h.session));
    addTearDown(value.dispose);
    return value;
  }

  test(
    'schedule loading/success retains inactive rows and server order',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      final flight = daily.schedules.load();
      expect(daily.schedules.request.loading, isTrue);
      await flight;
      expect(daily.schedules.items.map((s) => s.id).join(','), '30,31');
      expect(daily.schedules.items.last.active, isFalse);
      expect(daily.schedules.items.first.localTime, '08:00');
      expect(daily.schedules.items.first.timezone, 'Asia/Kolkata');
    },
  );
  test(
    'schedule empty, server error, network error and retry stay distinct',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      f.respond = (r) async => r.url.path.endsWith('/medication-schedules')
          ? healthEnvelope([], meta: pageMeta(total: 0))
          : null;
      await daily.schedules.load();
      expect(daily.schedules.initialized, isTrue);
      expect(daily.schedules.items, isEmpty);
      f.respond = (r) async => r.url.path.endsWith('/medication-schedules')
          ? healthError(500)
          : null;
      await daily.schedules.load();
      expect(daily.schedules.initialized, isFalse);
      expect(daily.schedules.request.error?.status, 500);
      f.respond = (r) async {
        if (r.url.path.endsWith('/medication-schedules')) {
          throw const SocketException('synthetic');
        }
        return null;
      };
      await daily.schedules.load();
      expect(daily.schedules.request.error?.kind, FailureKind.network);
      f.respond = null;
      await daily.schedules.load();
      expect(daily.schedules.items.length, 2);
    },
  );
  for (final due in [false, true]) {
    test(
      '${due ? 'due' : 'schedule'} paging sends real query and preserves order/deduplicates',
      () async {
        final f = await fixture();
        final (_, _, daily) = await scoped(f);
        f.respond = (r) async {
          if (!r.url.path.endsWith(
            due ? '/due-today' : '/medication-schedules',
          )) {
            return null;
          }
          expect(r.method, 'GET');
          expect(r.url.queryParameters.keys.toSet(), {'page', 'page_size'});
          expect(r.url.queryParameters['page_size'], '20');
          final second = r.url.queryParameters['page'] == '2';
          return healthEnvelope(
            due
                ? [dueData(second ? '32' : '31')]
                : [slotData(second ? '32' : '31')],
            meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second),
          );
        };
        if (due) {
          await daily.due.load();
          await daily.due.load(more: true);
          expect(daily.due.items.map((e) => e.id).join(','), '31,32');
        } else {
          await daily.schedules.load();
          await daily.schedules.load(more: true);
          expect(daily.schedules.items.map((e) => e.id).join(','), '31,32');
        }
      },
    );
  }
  test(
    'Due Today keeps null/taken/skipped, later times and slot-local dates',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      await daily.due.load();
      expect(daily.due.items.map((e) => e.state).toList(), [
        null,
        'taken',
        'skipped',
      ]);
      expect(daily.due.items.last.localTime, '23:59');
      expect(daily.due.items.last.scheduledOn.display, '17 Oct 2026');
      expect(daily.due.items.first.scheduledOn.display, '18 Oct 2026');
    },
  );
  test(
    'Due Today empty and error are not represented as no medicines',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      f.respond = (r) async => r.url.path.endsWith('/due-today')
          ? healthEnvelope([], meta: pageMeta(total: 0))
          : null;
      await daily.due.load();
      expect(daily.due.initialized, isTrue);
      expect(daily.due.items, isEmpty);
      f.respond = (r) async =>
          r.url.path.endsWith('/due-today') ? healthError(503) : null;
      await daily.due.load();
      expect(daily.due.initialized, isFalse);
      expect(daily.due.request.error?.status, 503);
    },
  );
  test(
    'observed slot-local date rollover during paging restarts from page one',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      var changed = false;
      f.respond = (r) async {
        if (!r.url.path.endsWith('/due-today')) return null;
        final second = r.url.queryParameters['page'] == '2';
        return healthEnvelope([
          {
            ...dueData(
              second
                  ? '31'
                  : changed
                  ? '40'
                  : '30',
            ),
            'scheduled_on': changed ? '2026-10-19' : '2026-10-18',
          },
        ], meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second));
      };
      await daily.due.load();
      changed = true;
      await daily.due.load(more: true);
      expect(daily.due.page, 1);
      expect(daily.due.items.single.id, '40');
      expect(daily.due.items.single.scheduledOn.display, '19 Oct 2026');
    },
  );
  test(
    'failed refresh after a detected day rollover never leaves yesterday visible',
    () async {
      final f = await fixture();
      final (_, _, daily) = await scoped(f);
      var changed = false;
      f.respond = (r) async {
        if (!r.url.path.endsWith('/due-today')) return null;
        final second = r.url.queryParameters['page'] == '2';
        if (changed && !second) return healthError(503);
        return healthEnvelope([
          {...dueData(), 'scheduled_on': changed ? '2026-10-19' : '2026-10-18'},
        ], meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second));
      };
      await daily.due.load();
      changed = true;
      await daily.due.load(more: true);
      expect(daily.due.items, isEmpty);
      expect(daily.due.initialized, isFalse);
      expect(daily.due.page, 0);
      expect(daily.due.request.error?.status, 503);
    },
  );
  for (final due in [false, true]) {
    test(
      'late profile A ${due ? 'due' : 'schedule'} response cannot populate B',
      () async {
        final f = await fixture();
        final (profiles, _, a) = await scoped(f);
        final delayed = Completer<http.Response>();
        final suffix = due ? '/due-today' : '/medication-schedules';
        f.respond = (r) async =>
            r.url.path.contains('/2/') && r.url.path.endsWith(suffix)
            ? delayed.future
            : null;
        final flight = due ? a.due.load() : a.schedules.load();
        await Future<void>.delayed(Duration.zero);
        // scoped() owns A's disposal in teardown; clear and invalidate its pending state now.
        a.due.clear();
        a.schedules.clear();
        profiles.choose();
        await profiles.select(HealthProfile.fromApi(profileData('3')));
        final healthB = HealthScope(profiles, profiles.selected!);
        final b = DailyHealthScope(healthB);
        addTearDown(() {
          b.dispose();
          healthB.dispose();
        });
        await (due ? b.due.load() : b.schedules.load());
        delayed.complete(
          healthEnvelope(
            due ? [dueData('99')] : [slotData('99')],
            meta: pageMeta(),
          ),
        );
        await flight;
        expect(a.due.items, isEmpty);
        expect(a.schedules.items, isEmpty);
        expect(due ? b.due.items.first.id : b.schedules.items.first.id, '30');
      },
    );
  }
  test(
    'schedule profile denial invalidates selection; account saved checks still work',
    () async {
      final f = await fixture();
      final (profiles, _, daily) = await scoped(f);
      final checks = saved(f);
      f.respond = (r) async => r.url.path.endsWith('/medication-schedules')
          ? healthError(404)
          : null;
      await daily.schedules.load();
      expect(profiles.selected, isNull);
      await checks.list.load();
      expect(checks.list.items.single.id, '40');
    },
  );
  test(
    'saved checks are independent of zero/multiple/changed profiles',
    () async {
      final f = await fixture();
      f.profiles = [];
      final checks = saved(f);
      await checks.list.load();
      expect(checks.list.items.length, 1);
      f.respond = (r) async {
        if (r.url.path.contains('/saved-symptom-checks')) {
          expect(
            r.url.queryParameters.keys.every(
              (key) => key == 'page' || key == 'page_size',
            ),
            isTrue,
          );
        }
        return null;
      };
      f.profiles = [profileData(), profileData('3')];
      final profiles = ProfileController(f.repository);
      addTearDown(profiles.dispose);
      await profiles.refresh();
      await profiles.select(profiles.list.value!.first);
      await profiles.select(profiles.list.value!.last);
      expect(checks.list.items.single.id, '40');
      await checks.open('40');
      expect(checks.detail.value?.metadata.id, '40');
      expect(
        f.paths
            .where(
              (p) => p.contains('/health-profiles/') && p.contains('saved'),
            )
            .isEmpty,
        isTrue,
      );
    },
  );
  test(
    'saved pagination, empty and retry do not reorder historical records',
    () async {
      final f = await fixture();
      final checks = saved(f);
      f.respond = (r) async {
        if (!r.url.path.endsWith('/saved-symptom-checks')) return null;
        final second = r.url.queryParameters['page'] == '2';
        return healthEnvelope(
          second ? [savedData('39')] : [savedData('40')],
          meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second),
        );
      };
      await checks.list.load();
      await checks.list.load(more: true);
      expect(checks.list.items.map((e) => e.id).join(','), '40,39');
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks')
          ? healthEnvelope([], meta: pageMeta(total: 0))
          : null;
      await checks.list.load();
      expect(checks.list.initialized, isTrue);
      expect(checks.list.items, isEmpty);
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks')
          ? healthError(500)
          : null;
      await checks.list.load();
      expect(checks.list.request.error?.status, 500);
      expect(checks.list.initialized, isFalse);
      f.respond = null;
      await checks.list.load();
      expect(checks.list.items.length, 1);
    },
  );
  test(
    'saved detail is frozen; missing/error detail never masquerades as a snapshot',
    () async {
      final f = await fixture();
      final checks = saved(f);
      await checks.open('40');
      expect(checks.detail.value?.results.first.displayPercent, 13);
      expect(checks.detail.value?.results.last.displayPercent, 99);
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks/40')
          ? healthError(404)
          : null;
      await checks.open('40');
      expect(checks.detail.value, isNull);
      expect(checks.detail.error?.status, 404);
      f.respond = null;
      await checks.open('40');
      expect(checks.detail.value?.disclaimer, disclaimer);
      expect(
        f.paths.any(
          (p) => p.contains('symptom-checker') || p.contains('/diseases'),
        ),
        isFalse,
      );
    },
  );
  test(
    'late saved detail cannot overwrite a newly opened saved check',
    () async {
      final f = await fixture();
      final checks = saved(f);
      final delayed = Completer<http.Response>();
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks/40')
          ? delayed.future
          : null;
      final flight = checks.open('40');
      await Future<void>.delayed(Duration.zero);
      await checks.open('41');
      delayed.complete(healthEnvelope(savedDetailData()));
      await flight;
      expect(checks.detail.value?.metadata.id, '41');
    },
  );
  test(
    'logout clears account-owned state and ignores in-flight saved response',
    () async {
      final f = await fixture();
      final checks = saved(f);
      await checks.list.load();
      final delayed = Completer<http.Response>();
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks/40')
          ? delayed.future
          : null;
      final flight = checks.open('40');
      await Future<void>.delayed(Duration.zero);
      await f.h.session.logout();
      delayed.complete(healthEnvelope(savedDetailData()));
      await flight;
      expect(checks.list.items, isEmpty);
      expect(checks.detail.value, isNull);
    },
  );
  test(
    '429 observes Retry-After; repeated 401 uses existing session termination',
    () async {
      final f = await fixture();
      final checks = saved(f);
      var calls = 0;
      f.respond = (r) async {
        if (r.url.path.endsWith('/saved-symptom-checks')) {
          ++calls;
          return healthError(429, retry: 30);
        }
        return null;
      };
      await checks.list.load();
      await checks.list.load();
      expect(calls, 1);
      expect(checks.list.request.retryAllowed, isFalse);
      f.respond = (r) async => r.url.path.endsWith('/saved-symptom-checks/40')
          ? healthError(401)
          : null;
      await checks.open('40');
      expect(f.h.session.identity, isNull);
      expect(checks.list.items, isEmpty);
    },
  );
}
