import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:kobi_mobile/app/environment.dart';
import 'package:kobi_mobile/core/network/api_client.dart';
import 'package:kobi_mobile/core/storage/session_store.dart';
import 'package:kobi_mobile/features/auth/auth_repository.dart';
import 'package:kobi_mobile/features/auth/session_controller.dart';
import 'package:kobi_mobile/features/auth/token_bundle.dart';

final clock = DateTime.utc(2026, 10, 18, 12);
String credential(String prefix, int generation) =>
    '${prefix}_${List.filled(42, String.fromCharCode(65 + generation)).join()}A';
Map<String, Object?> tokenData(int generation, {int expiresIn = 600}) => {
  'token_type': 'Bearer',
  'access_token': credential('ka1', generation),
  'expires_in': expiresIn,
  'refresh_token': credential('kr1', generation),
  'refresh_expires_at': clock.add(const Duration(days: 14)).toIso8601String(),
  'session_id': '0123456789abcdef0123456789abcdef',
  'session_expires_at': clock.add(const Duration(days: 30)).toIso8601String(),
};
Map<String, Object?> identityData() => {
  'id': '1',
  'email': 'fixture@example.invalid',
  'status': 'active',
  'email_verified': true,
};
http.Response ok(Map<String, Object?> data) => http.Response(
  jsonEncode({
    'success': true,
    'data': data,
    'meta': {'api_version': 'v1'},
  }),
  200,
  headers: {'content-type': 'application/json; charset=UTF-8'},
);
http.Response denied({String code = 'UNAUTHENTICATED'}) => http.Response(
  jsonEncode({
    'success': false,
    'error': {'code': code, 'message': 'Safe denial'},
    'meta': {'api_version': 'v1'},
  }),
  401,
  headers: {'content-type': 'application/json'},
);

class MemoryStore implements SessionStore {
  TokenBundle? bundle;
  bool pending = false,
      failSave = false,
      failClear = false,
      failPending = false;
  int saves = 0, clears = 0;
  @override
  Future<TokenBundle?> read() async => pending ? null : bundle;
  @override
  Future<void> save(TokenBundle value) async {
    if (failSave) throw StateError('synthetic store failure');
    ++saves;
    bundle = value;
    pending = false;
  }

  @override
  Future<void> markRotationPending() async {
    if (failPending) throw StateError('synthetic storage failure');
    bundle = null;
    pending = true;
  }

  @override
  Future<void> clear() async {
    if (failClear) throw StateError('synthetic clear failure');
    ++clears;
    bundle = null;
    pending = false;
  }
}

class Harness {
  Harness(
    Future<http.Response> Function(http.Request request) handle, {
    MemoryStore? store,
  }) : store = store ?? MemoryStore() {
    api = ApiClient(
      Environment(
        name: 'local',
        apiBaseUrl: 'https://fixture.example.invalid/api/v1',
      ),
      MockClient(handle),
    );
    repository = AuthRepository(api, now: () => time);
    session = SessionController(repository, this.store, now: () => time);
  }
  DateTime time = clock;
  final MemoryStore store;
  late final ApiClient api;
  late final AuthRepository repository;
  late final SessionController session;
  void dispose() {
    session.dispose();
    api.close();
  }
}
