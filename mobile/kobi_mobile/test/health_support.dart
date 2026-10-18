import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/features/auth/token_bundle.dart';
import 'package:kobi_mobile/features/health/health_repository.dart';
import 'support.dart';

Map<String, Object?> profileData([String id = '2']) => {
  'id': id,
  'full_name': id == '2' ? 'Fixture A' : 'Fixture B',
  'profile_type': id == '2' ? 'self' : 'dependent',
};
Map<String, Object?> summaryData([String id = '2', int count = 1]) => {
  'profile': profileData(id),
  'counts': {
    'conditions': count,
    'historical_conditions': 0,
    'current_medications': count,
    'allergies': 0,
    'procedures': 0,
  },
};
Map<String, Object?> conditionData([String id = '10']) => {
  'id': id,
  'condition_name': 'Fixture condition $id',
  'status': 'chronic',
  'diagnosed_on': '2026-01-10',
  'resolved_on': null,
};
Map<String, Object?> medicineData([String id = '20']) => {
  'id': id,
  'brand_name': 'Fixture medicine $id',
  'generic_name': null,
  'strength': '8 mg',
  'dose': null,
  'frequency': null,
  'route': null,
  'status': 'current',
  'started_on': null,
  'stopped_on': null,
};
http.Response healthEnvelope(
  Object data, {
  Map<String, Object?> meta = const {},
}) => http.Response(
  jsonEncode({
    'success': true,
    'data': data,
    'meta': {'api_version': 'v1', ...meta},
  }),
  200,
  headers: {'content-type': 'application/json'},
);
http.Response healthError(int status, {int? retry}) => http.Response(
  jsonEncode({
    'success': false,
    'error': {
      'code': status == 404 ? 'NOT_FOUND' : 'UNAVAILABLE',
      'message': 'Private internal text must never be displayed',
    },
    'meta': {'api_version': 'v1'},
  }),
  status,
  headers: {
    'content-type': 'application/json',
    if (retry != null) 'retry-after': '$retry',
  },
);
Map<String, Object?> pageMeta({
  int page = 1,
  int total = 1,
  bool more = false,
}) => {'page': page, 'page_size': 20, 'total': total, 'has_more': more};

class HealthFixture {
  HealthFixture() {
    h = Harness(handle);
    h.store.bundle = TokenBundle.fromApi(tokenData(0), clock);
    repository = HealthRepository(h.api, h.session);
  }
  late final Harness h;
  late final HealthRepository repository;
  List<Map<String, Object?>> profiles = [profileData()];
  final paths = <String>[];
  Future<http.Response?> Function(http.Request)? intercept;
  Future<void> ready() => h.session.restore();
  Future<http.Response> handle(http.Request request) async {
    final path = request.url.path.replaceFirst('/api/v1', '');
    paths.add(path);
    final intercepted = await intercept?.call(request);
    if (intercepted != null) return intercepted;
    if (path == '/auth/login' || path == '/auth/refresh') {
      return ok(tokenData(path.endsWith('refresh') ? 1 : 0));
    }
    if (path == '/me') return ok(identityData());
    if (path == '/auth/logout' || path == '/auth/logout-all') {
      return ok({'revoked': true});
    }
    if (path == '/health-profiles') return healthEnvelope(profiles);
    final parts = path.split('/');
    if (parts.length >= 3 && parts[1] == 'health-profiles') {
      final id = parts[2];
      if (parts.length == 3) return healthEnvelope(profileData(id));
      switch (parts[3]) {
        case 'medical-summary':
          return healthEnvelope(summaryData(id));
        case 'conditions':
          return parts.length == 5
              ? healthEnvelope(conditionData(parts[4]))
              : healthEnvelope([conditionData()], meta: pageMeta());
        case 'medications':
          return parts.length == 5
              ? healthEnvelope(medicineData(parts[4]))
              : healthEnvelope([medicineData()]);
      }
    }
    return healthError(404);
  }

  void dispose() => h.dispose();
}
