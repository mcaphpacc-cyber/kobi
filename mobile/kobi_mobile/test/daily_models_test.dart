import 'package:flutter_test/flutter_test.dart';
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/daily_health/schedule_models.dart';
import 'package:kobi_mobile/features/saved_checks/saved_models.dart';
import 'package:kobi_mobile/features/health/paged_state.dart';
import 'daily_support.dart';
import 'health_support.dart';

void main() {
  test(
    'schedule projection preserves string references, wall time, zone, active and null end',
    () {
      final slot = MedicationSlot.fromApi(slotData());
      expect(slot.id, '30');
      expect(slot.medicineId, '9007199254740993');
      expect(slot.localTime, '08:00');
      expect(slot.timezone, 'Asia/Kolkata');
      expect(slot.starts.display, '18 Oct 2026');
      expect(slot.ends, isNull);
      expect(slot.active, isTrue);
      final inactive = MedicationSlot.fromApi({
        ...slotData('31', false),
        'ends_on': '2026-10-19',
      });
      expect(inactive.active, isFalse);
      expect(inactive.ends?.display, '19 Oct 2026');
    },
  );
  for (final state in <String?>[null, 'taken', 'skipped']) {
    test('Due Today parses $state with the server-selected calendar day', () {
      final entry = DueEntry.fromApi(dueData('30', state));
      expect(entry.state, state);
      expect(entry.scheduledOn.display, '18 Oct 2026');
      expect(entry.localTime, '08:00');
      expect(entry.timezone, 'Asia/Kolkata');
      expect(
        entry.stateLabel,
        state == null
            ? 'No checkoff recorded'
            : state == 'taken'
            ? 'Taken'
            : 'Skipped',
      );
    });
  }
  test(
    'another zone keeps its own time and date, including a later-time entry',
    () {
      final value = DueEntry.fromApi({
        ...dueData(),
        'local_time': '23:59',
        'timezone': 'America/New_York',
        'scheduled_on': '2026-10-17',
      });
      expect(value.localTime, '23:59');
      expect(value.timezone, 'America/New_York');
      expect(value.scheduledOn.display, '17 Oct 2026');
    },
  );
  test(
    'invalid schedule/occurrence fields fail instead of inventing values',
    () {
      for (final input in [
        {...slotData(), 'local_time': '24:00'},
        {...slotData(), 'timezone': ''},
        {...slotData(), 'is_active': 1},
        {...slotData(), 'starts_on': null},
      ]) {
        expect(() => MedicationSlot.fromApi(input), throwsA(isA<ApiFailure>()));
      }
      expect(
        () => DueEntry.fromApi({...dueData(), 'state': 'missed'}),
        throwsA(isA<ApiFailure>()),
      );
    },
  );
  test(
    'saved metadata keeps UTC wire values; only presentation converts to local',
    () {
      final check = SavedCheckMetadata.fromApi(savedData('9007199254740993'));
      expect(check.id, '9007199254740993');
      expect(check.checked.raw, '2026-01-01T01:02:03Z');
      expect(check.checked.utc, DateTime.utc(2026, 1, 1, 1, 2, 3));
      final local = check.checked.utc.toLocal();
      expect(
        check.checked.display.contains(
          '${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}',
        ),
        isTrue,
      );
      expect(check.selectedCount, 2);
      expect(check.resultCount, 2);
    },
  );
  test(
    'saved detail preserves historical order, labels, summary, scores, text and disclaimer',
    () {
      final value = SavedCheckDetail.fromApi(savedDetailData());
      expect(value.version, 1);
      expect(value.selected.map((s) => s.id).join(','), '2,1');
      expect(value.results.map((r) => r.id).join(','), '90,3');
      expect(value.results.map((r) => r.displayPercent).join(','), '13,99');
      expect(value.results.first.userScore, 75.5);
      expect(value.results.first.coverage, 40.25);
      expect(value.results.first.matchLevel, 'Weak Match');
      expect(
        value.results.first.reason.join('|'),
        'Original reason two|Original reason one',
      );
      expect(value.results.first.explanation.single, 'Original explanation');
      expect(value.totalMatches, 9);
      expect(value.displayedMatches, 2);
      expect(value.disclaimer, disclaimer);
    },
  );
  test('empty results are a legitimate historical snapshot', () {
    final data = savedDetailData();
    data['displayed_result_count'] = 0;
    final snapshot = data['snapshot'] as Map<String, Object?>;
    snapshot['results'] = <Object?>[];
    snapshot['summary'] = {'total_matches': 0, 'displayed_matches': 0};
    expect(SavedCheckDetail.fromApi(data).results, isEmpty);
  });
  test('unsupported version and inconsistent snapshot metadata are errors', () {
    final version = savedDetailData();
    (version['snapshot'] as Map<String, Object?>)['version'] = 2;
    expect(() => SavedCheckDetail.fromApi(version), throwsA(isA<ApiFailure>()));
    final mismatch = savedDetailData()..['selected_symptom_count'] = 3;
    expect(
      () => SavedCheckDetail.fromApi(mismatch),
      throwsA(isA<ApiFailure>()),
    );
    expect(
      () => SavedInstant.fromApi('2026-02-30T01:02:03Z'),
      throwsA(isA<ApiFailure>()),
    );
    expect(
      () => SavedInstant.fromApi('2026-01-01 01:02:03'),
      throwsA(isA<ApiFailure>()),
    );
    expect(
      () => SavedResult.fromApi({...resultData(), 'coverage': double.infinity}),
      throwsA(isA<ApiFailure>()),
    );
  });
  test('all collections use real pagination fields and empty arrays', () {
    expect(
      ReadPage<MedicationSlot>.fromApi(
        [],
        pageMeta(total: 0),
        MedicationSlot.fromApi,
      ).items,
      isEmpty,
    );
    expect(
      ReadPage<DueEntry>.fromApi(
        [],
        pageMeta(total: 0),
        DueEntry.fromApi,
      ).items,
      isEmpty,
    );
    expect(
      ReadPage<SavedCheckMetadata>.fromApi(
        [],
        pageMeta(total: 0),
        SavedCheckMetadata.fromApi,
      ).items,
      isEmpty,
    );
    expect(
      ReadPage<MedicationSlot>.fromApi(
        [slotData()],
        pageMeta(total: 21, more: true),
        MedicationSlot.fromApi,
      ).hasMore,
      isTrue,
    );
    for (final meta in [
      <String, dynamic>{},
      {...pageMeta(), 'page_size': 51},
      {...pageMeta(), 'has_more': true},
    ]) {
      expect(
        () =>
            ReadPage<MedicationSlot>.fromApi([], meta, MedicationSlot.fromApi),
        throwsA(isA<ApiFailure>()),
      );
    }
  });
}
