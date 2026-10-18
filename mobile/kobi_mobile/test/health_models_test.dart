import 'package:flutter_test/flutter_test.dart';
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/health/health_models.dart';
import 'health_support.dart';

void main() {
  test(
    'profile list/detail keeps decimal string IDs and only exposed identity',
    () {
      final value = HealthProfile.fromApi({
        ...profileData('9007199254740993'),
        'ignored': 'additive',
      });
      expect(value.id, '9007199254740993');
      expect(value.type, 'dependent');
      expect(collection([profileData()], HealthProfile.fromApi).length, 1);
      expect(collection([], HealthProfile.fromApi), isEmpty);
    },
  );
  test('summary parses five real counts including legitimate zero values', () {
    final value = MedicalSummary.fromApi(summaryData('2', 0));
    expect(value.counts.length, 5);
    expect(value.counts.values.every((count) => count == 0), isTrue);
    expect(value.profile.id, '2');
  });
  test(
    'condition list/detail parses recorded status and nullable calendar dates',
    () {
      final value = MedicalCondition.fromApi(conditionData());
      expect(value.id, '10');
      expect(value.status, 'chronic');
      expect(value.diagnosed?.display, '10 Jan 2026');
      expect(value.resolved, isNull);
      final page = ConditionPage.fromApi(
        [conditionData()],
        {'api_version': 'v1', ...pageMeta()},
      );
      expect(page.items.length, 1);
      expect(page.hasMore, isFalse);
      expect(ConditionPage.fromApi([], pageMeta(total: 0)).items, isEmpty);
    },
  );
  test('medicine list/detail parses every personal field and null dates', () {
    final value = PersonalMedicine.fromApi(medicineData());
    expect(value.id, '20');
    expect(value.strength, '8 mg');
    expect(value.started, isNull);
    expect(value.stopped, isNull);
    expect(value.generic, isNull);
    final full = PersonalMedicine.fromApi({
      ...medicineData(),
      'generic_name': 'Recorded name',
      'dose': 'Recorded dose',
      'frequency': 'Recorded frequency',
      'route': 'Oral',
      'status': 'stopped',
      'started_on': '2024-02-29',
      'stopped_on': '2026-10-18',
    });
    expect(full.started?.display, '29 Feb 2024');
    expect(full.stopped?.display, '18 Oct 2026');
    expect(full.status, 'stopped');
    expect(collection([], PersonalMedicine.fromApi), isEmpty);
  });
  for (final value in [
    '2026-02-29',
    '2026-13-01',
    '2026-01-00',
    '2026-1-01',
    '2026-01-10T00:00:00Z',
    '0000-01-01',
  ]) {
    test('calendar parser rejects invalid date shape/value $value', () {
      expect(() => CalendarDate.fromApi(value), throwsA(isA<ApiFailure>()));
    });
  }
  test(
    'malformed fields fail closed instead of fabricating empty health data',
    () {
      for (final invalid in [
        null,
        <Object?>[],
        {'id': 2},
        {...profileData(), 'profile_type': 'owner'},
        {...profileData(), 'id': '02'},
      ]) {
        expect(
          () => HealthProfile.fromApi(invalid),
          throwsA(isA<ApiFailure>()),
        );
      }
      expect(
        () => MedicalSummary.fromApi({
          'profile': profileData(),
          'counts': {'conditions': -1},
        }),
        throwsA(isA<ApiFailure>()),
      );
      expect(
        () =>
            MedicalCondition.fromApi({...conditionData(), 'status': 'severe'}),
        throwsA(isA<ApiFailure>()),
      );
      expect(
        () => PersonalMedicine.fromApi({...medicineData(), 'dose': 2}),
        throwsA(isA<ApiFailure>()),
      );
      expect(
        () =>
            PersonalMedicine.fromApi({...medicineData()}..remove('started_on')),
        throwsA(isA<ApiFailure>()),
      );
      expect(
        () => collection({}, HealthProfile.fromApi),
        throwsA(isA<ApiFailure>()),
      );
    },
  );
  test('real pagination fields are required and bounds checked', () {
    final page = ConditionPage.fromApi([
      conditionData(),
    ], pageMeta(total: 21, more: true));
    expect(page.hasMore, isTrue);
    for (final meta in [
      <String, Object?>{},
      {...pageMeta(), 'page': '1'},
      {...pageMeta(), 'page_size': 51},
      {...pageMeta(), 'total': -1},
      {...pageMeta(), 'has_more': true},
    ]) {
      expect(
        () => ConditionPage.fromApi([], Map<String, dynamic>.from(meta)),
        throwsA(isA<ApiFailure>()),
      );
    }
  });
}
