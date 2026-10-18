import 'package:http/http.dart' as http;
import 'health_support.dart';

Map<String, Object?> slotData([String id = '30', bool active = true]) => {
  'id': id,
  'health_medicine_id': '9007199254740993',
  'local_time': '08:00',
  'timezone': 'Asia/Kolkata',
  'starts_on': '2026-10-18',
  'ends_on': null,
  'is_active': active,
};
Map<String, Object?> dueData([String id = '30', String? state]) => {
  'id': id,
  'health_medicine_id': '9007199254740993',
  'local_time': '08:00',
  'timezone': 'Asia/Kolkata',
  'scheduled_on': '2026-10-18',
  'state': state,
};
Map<String, Object?> savedData([String id = '40']) => {
  'id': id,
  'checked_at': '2026-01-01T01:02:03Z',
  'saved_at': '2026-01-02T04:05:06Z',
  'selected_symptom_count': 2,
  'displayed_result_count': 2,
};
Map<String, Object?> resultData([String id = '90', int percent = 13]) => {
  'id': id,
  'label': 'Historical result $id',
  'display_percent': percent,
  'match_level': 'Weak Match',
  'user_match_score': 75.5,
  'coverage': 40.25,
  'reason': ['Original reason two', 'Original reason one'],
  'explanation': ['Original explanation'],
};
const disclaimer =
    'KOBI Symptom Checker is an educational tool. It does not provide a medical diagnosis.';
Map<String, Object?> savedDetailData([String id = '40']) => {
  ...savedData(id),
  'snapshot': {
    'version': 1,
    'checked_at': '2026-01-01T01:02:03Z',
    'selected': [
      {'id': '2', 'label': 'Historical cough'},
      {'id': '1', 'label': 'Historical fever'},
    ],
    'summary': {'total_matches': 9, 'displayed_matches': 2},
    'results': [resultData(), resultData('3', 99)],
    'disclaimer': disclaimer,
  },
};

class DailyFixture extends HealthFixture {
  DailyFixture() {
    intercept = _daily;
  }
  Future<http.Response?> Function(http.Request)? respond;
  Future<http.Response?> _daily(http.Request r) async {
    final overridden = await respond?.call(r);
    if (overridden != null) return overridden;
    if (r.url.path.endsWith('/medication-schedules/due-today')) {
      return healthEnvelope([
        dueData(),
        dueData('31', 'taken'),
        {
          ...dueData('32', 'skipped'),
          'local_time': '23:59',
          'timezone': 'America/New_York',
          'scheduled_on': '2026-10-17',
        },
      ], meta: pageMeta(total: 3));
    }
    if (r.url.path.endsWith('/medication-schedules')) {
      return healthEnvelope([
        slotData(),
        slotData('31', false),
      ], meta: pageMeta(total: 2));
    }
    if (r.url.path.endsWith('/saved-symptom-checks')) {
      return healthEnvelope([savedData()], meta: pageMeta());
    }
    if (r.url.path.contains('/saved-symptom-checks/')) {
      return healthEnvelope(savedDetailData(r.url.pathSegments.last));
    }
    return null;
  }
}
