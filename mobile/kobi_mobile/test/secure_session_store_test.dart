import 'dart:convert';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/core/storage/session_store.dart';
import 'package:kobi_mobile/features/auth/token_bundle.dart';
import 'support.dart';

void main() {
  final binding = TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');
  const barrier = MethodChannel('org.kobi.mobile/secure_session');
  String? stored;
  var failDelete = false, failWrite = false;
  late List<MethodCall> calls;
  setUp(() {
    stored = null;
    failDelete = false;
    failWrite = false;
    calls = [];
    binding.defaultBinaryMessenger.setMockMethodCallHandler(barrier, (
      call,
    ) async {
      calls.add(call);
      return true;
    });
    binding.defaultBinaryMessenger.setMockMethodCallHandler(channel, (
      call,
    ) async {
      calls.add(call);
      final arguments = call.arguments as Map<Object?, Object?>;
      switch (call.method) {
        case 'write':
          if (failWrite) {
            throw PlatformException(
              code: 'locked',
              message: 'synthetic private native error',
            );
          }
          stored = arguments['value'] as String?;
          return null;
        case 'read':
          return stored;
        case 'delete':
          if (failDelete) throw PlatformException(code: 'locked');
          stored = null;
          return null;
        default:
          throw UnsupportedError('Unexpected storage method');
      }
    });
  });
  tearDown(() {
    binding.defaultBinaryMessenger.setMockMethodCallHandler(channel, null);
    binding.defaultBinaryMessenger.setMockMethodCallHandler(barrier, null);
  });
  test('one protected record roundtrips pair and binds environment', () async {
    final store = SecureSessionStore('local|https://fixture/api/v1');
    final bundle = TokenBundle.fromApi(tokenData(0), clock);
    await store.save(bundle);
    expect((await store.read())!.refreshToken, bundle.refreshToken);
    final data = jsonDecode(stored!) as Map<String, dynamic>;
    expect(data.keys.toSet(), {'version', 'scope', 'state', 'bundle'});
    expect(data.toString(), isNot(contains('password')));
    final writes = calls.where((call) => call.method == 'write').toList();
    expect(writes.length, 1);
    expect(calls.where((call) => call.method == 'flush').length, 1);
    final other = SecureSessionStore('production|https://other/api/v1');
    expect(await other.read(), isNull);
    expect(stored, isNull);
  });
  test(
    'pending rotation has no persisted credentials and cannot be restored',
    () async {
      final store = SecureSessionStore('local');
      await store.save(TokenBundle.fromApi(tokenData(0), clock));
      await store.markRotationPending();
      expect(stored, contains('rotation_pending'));
      expect(stored, isNot(contains('access_token')));
      expect(stored, isNot(contains('refresh_token')));
      expect(await store.read(), isNull);
    },
  );
  test('clear leaves no credentials even if native delete fails', () async {
    final store = SecureSessionStore('local');
    await store.save(TokenBundle.fromApi(tokenData(0), clock));
    failDelete = true;
    await store.clear();
    expect(stored, contains('signed_out'));
    expect(stored, isNot(contains('access_token')));
    expect(await store.read(), isNull);
  });
  test(
    'native read/write failure is safe and not silently treated as a saved session',
    () async {
      final store = SecureSessionStore('local');
      failWrite = true;
      await expectLater(
        store.save(TokenBundle.fromApi(tokenData(0), clock)),
        throwsA(
          isA<ApiFailure>().having((e) => e.kind, 'kind', FailureKind.storage),
        ),
      );
      expect(stored, isNull);
    },
  );
  test('malformed storage is not interpreted as authenticated', () async {
    stored = '{not json';
    await expectLater(
      SecureSessionStore('local').read(),
      throwsA(isA<ApiFailure>()),
    );
  });
  test(
    'failed Android durability barrier prevents successful persistence',
    () async {
      binding.defaultBinaryMessenger.setMockMethodCallHandler(
        barrier,
        (_) async => false,
      );
      await expectLater(
        SecureSessionStore(
          'local',
        ).save(TokenBundle.fromApi(tokenData(0), clock)),
        throwsA(
          isA<ApiFailure>().having((e) => e.kind, 'kind', FailureKind.storage),
        ),
      );
    },
  );
}
