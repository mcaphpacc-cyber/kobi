import 'dart:async';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:kobi_mobile/app/environment.dart';
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/core/network/api_client.dart';

import 'support.dart';

void main() {
  final environment = Environment(
    name: 'local',
    apiBaseUrl: 'https://fixture.example.invalid/kobi/public/api/v1',
  );
  test('HTTPS and explicit environment are mandatory, including local', () {
    for (final url in [
      '',
      'http://host/api/v1',
      'https://user:secret@host/api/v1',
      'https://host/api/v1?q=x',
      'https://host/api/v1#x',
      'https://host/legacy',
    ]) {
      expect(
        () => Environment(name: 'production', apiBaseUrl: url),
        throwsFormatException,
      );
    }
    expect(
      () => Environment(name: 'unknown', apiBaseUrl: 'https://host/api/v1'),
      throwsFormatException,
    );
  });
  test(
    'correct envelope, mount, no cookies, JSON empty object and no redirects',
    () async {
      final api = ApiClient(
        environment,
        MockClient((request) async {
          expect(
            request.url.toString(),
            'https://fixture.example.invalid/kobi/public/api/v1/auth/logout',
          );
          expect(request.followRedirects, isFalse);
          expect(
            request.headers.keys.map((key) => key.toLowerCase()),
            isNot(contains('cookie')),
          );
          expect(request.headers['Content-Type'], 'application/json');
          expect(request.body, '{}');
          return ok({'revoked': true});
        }),
      );
      addTearDown(api.close);
      expect(
        await api.request(
          'POST',
          '/auth/logout',
          accessToken: credential('ka1', 0),
        ),
        {'revoked': true},
      );
    },
  );
  test(
    'public request has no Authorization; invalid relative paths and token leakage rejected',
    () async {
      final api = ApiClient(
        environment,
        MockClient((request) async {
          expect(request.headers.containsKey('Authorization'), isFalse);
          return ok({'example': true});
        }),
      );
      addTearDown(api.close);
      await api.request('GET', '/diseases/example');
      for (final path in [
        '/diseases/example',
        '/substances/search',
        '/auth/login',
        '/auth/refresh',
      ]) {
        await expectLater(
          api.request('GET', path, accessToken: 'secret'),
          throwsA(isA<ApiFailure>()),
        );
      }
      for (final path in [
        'https://other/me',
        '//other/me',
        '/../me',
        '/me?token=x',
        '/me#fragment',
        '/\\other',
      ]) {
        await expectLater(api.request('GET', path), throwsA(isA<ApiFailure>()));
      }
    },
  );
  test('API denial is typed, no raw server message is surfaced', () async {
    final api = ApiClient(
      environment,
      MockClient(
        (_) async => http.Response(
          '{"success":false,"error":{"code":"AUTHENTICATION_FAILED","message":"SQL secret raw exception"},"meta":{"api_version":"v1"}}',
          401,
          headers: {'content-type': 'application/json'},
        ),
      ),
    );
    addTearDown(api.close);
    await expectLater(
      api.request('POST', '/auth/login'),
      throwsA(
        isA<ApiFailure>()
            .having((e) => e.kind, 'kind', FailureKind.authentication)
            .having(
              (e) => e.message,
              'safe message',
              'Unable to sign in with these credentials.',
            ),
      ),
    );
  });
  test('Retry-After is retained without exposing server internals', () async {
    final api = ApiClient(
      environment,
      MockClient(
        (_) async => http.Response(
          '{"success":false,"error":{"code":"RATE_LIMITED","message":"raw"},"meta":{"api_version":"v1"}}',
          429,
          headers: {'content-type': 'application/json', 'retry-after': '17'},
        ),
      ),
    );
    addTearDown(api.close);
    await expectLater(
      api.request('GET', '/me'),
      throwsA(isA<ApiFailure>().having((e) => e.retryAfter, 'retry', 17)),
    );
  });
  test('network, DNS and timeout are distinct safe failures', () async {
    final network = ApiClient(
      environment,
      MockClient((_) async => throw const SocketException('private details')),
    );
    addTearDown(network.close);
    await expectLater(
      network.request('GET', '/me'),
      throwsA(
        isA<ApiFailure>().having((e) => e.kind, 'network', FailureKind.network),
      ),
    );
    final timeout = ApiClient(
      environment,
      MockClient((_) => Completer<http.Response>().future),
      timeout: const Duration(milliseconds: 5),
    );
    addTearDown(timeout.close);
    await expectLater(
      timeout.request('GET', '/me'),
      throwsA(
        isA<ApiFailure>().having((e) => e.kind, 'timeout', FailureKind.timeout),
      ),
    );
  });
  test(
    'HTML, malformed, wrong envelope/version and redirects fail safely',
    () async {
      for (final response in [
        http.Response('<html>private</html>', 500),
        http.Response('{', 200, headers: {'content-type': 'application/json'}),
        http.Response(
          '{"success":true,"data":{},"meta":{"api_version":"v2"}}',
          200,
          headers: {'content-type': 'application/json'},
        ),
        http.Response(
          '{"success":true,"data":{},"meta":{"api_version":"v1"}}',
          302,
          headers: {
            'content-type': 'application/json',
            'location': 'https://other/',
          },
        ),
        http.Response(
          'x' * 262145,
          200,
          headers: {'content-type': 'application/json'},
        ),
      ]) {
        final api = ApiClient(environment, MockClient((_) async => response));
        await expectLater(
          api.request('GET', '/me'),
          throwsA(
            isA<ApiFailure>().having(
              (e) => e.kind,
              'protocol',
              FailureKind.protocol,
            ),
          ),
        );
        api.close();
      }
    },
  );
}
