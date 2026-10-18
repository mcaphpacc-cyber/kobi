import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/auth/token_bundle.dart';
import 'package:kobi_mobile/features/auth/session_controller.dart';

import 'support.dart';

void main() {
  test(
    'genuine contract login persists pair before me; validates server identity',
    () async {
      late Harness h;
      h = Harness((request) async {
        if (request.url.path.endsWith('/login')) {
          expect(jsonDecode(request.body), {
            'email': 'fixture@example.invalid',
            'password': ' password spaces ',
            'device_label': 'KOBI Mobile',
          });
          expect(request.headers['Authorization'], isNull);
          return ok(tokenData(0));
        }
        expect(h.store.bundle, isNotNull);
        expect(
          request.headers['Authorization'],
          'Bearer ${credential('ka1', 0)}',
        );
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login(' fixture@example.invalid ', ' password spaces ');
      expect(h.session.authenticated, isTrue);
      expect(h.session.identity!.email, 'fixture@example.invalid');
      expect(h.store.saves, 1);
      expect(h.store.bundle.toString(), isNot(contains(credential('ka1', 0))));
    },
  );
  test('rejected login does not refresh and clears local state', () async {
    var calls = 0;
    final h = Harness((_) async {
      ++calls;
      return denied(code: 'AUTHENTICATION_FAILED');
    });
    addTearDown(h.dispose);
    await h.session.restore();
    await h.session.login('fixture@example.invalid', 'wrong');
    expect(calls, 1);
    expect(h.store.bundle, isNull);
    expect(h.session.state, SessionState.anonymous);
    expect(h.session.message, 'Unable to sign in with these credentials.');
  });
  test(
    'restoration validates unexpired access with me, without login/refresh',
    () async {
      final store = MemoryStore()
        ..bundle = TokenBundle.fromApi(tokenData(0), clock);
      final h = Harness((request) async {
        expect(request.url.path.endsWith('/me'), isTrue);
        return ok(identityData());
      }, store: store);
      addTearDown(h.dispose);
      await h.session.restore();
      expect(h.session.authenticated, isTrue);
      expect(store.saves, 0);
    },
  );
  test(
    'expired access on restart refreshes once before validating me',
    () async {
      final store = MemoryStore()
        ..bundle = TokenBundle.fromApi(
          tokenData(0),
          clock.subtract(const Duration(minutes: 11)),
        );
      var refreshes = 0;
      final h = Harness((request) async {
        if (request.url.path.endsWith('/refresh')) {
          ++refreshes;
          expect(store.pending, isTrue);
          expect(store.bundle, isNull);
          expect(jsonDecode(request.body), {
            'refresh_token': credential('kr1', 0),
          });
          return ok(tokenData(1));
        }
        expect(
          request.headers['Authorization'],
          'Bearer ${credential('ka1', 1)}',
        );
        return ok(identityData());
      }, store: store);
      addTearDown(h.dispose);
      await h.session.restore();
      expect(refreshes, 1);
      expect(h.session.authenticated, isTrue);
      expect(store.saves, 1);
    },
  );
  test(
    'five simultaneous 401s share one rotation and each retry once',
    () async {
      var expired = false, refreshes = 0, oldCalls = 0, newCalls = 0;
      final release = Completer<void>();
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/refresh')) {
          ++refreshes;
          await release.future;
          return ok(tokenData(1));
        }
        if (expired &&
            request.headers['Authorization'] ==
                'Bearer ${credential('ka1', 0)}') {
          ++oldCalls;
          return denied();
        }
        if (expired) ++newCalls;
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      expired = true;
      final operations = List.generate(
        5,
        (_) => h.session.authorized(h.repository.me),
      );
      await Future<void>.delayed(const Duration(milliseconds: 20));
      expect(refreshes, 1);
      release.complete();
      await Future.wait(operations);
      expect(oldCalls, 5);
      expect(newCalls, 5);
      expect(refreshes, 1);
      expect(h.store.saves, 2);
    },
  );
  test('second 401 ends session without infinite refresh', () async {
    var fail = false, refreshes = 0, reads = 0;
    final h = Harness((request) async {
      if (request.url.path.endsWith('/login')) return ok(tokenData(0));
      if (request.url.path.endsWith('/refresh')) {
        ++refreshes;
        return ok(tokenData(1));
      }
      if (fail) {
        ++reads;
        return denied();
      }
      return ok(identityData());
    });
    addTearDown(h.dispose);
    await h.session.restore();
    await h.session.login('fixture@example.invalid', 'password');
    fail = true;
    await expectLater(
      h.session.authorized(h.repository.me),
      throwsA(isA<ApiFailure>()),
    );
    expect(refreshes, 1);
    expect(reads, 2);
    expect(h.session.state, SessionState.anonymous);
    expect(h.store.bundle, isNull);
  });
  for (final mode in ['reused', 'network', 'server']) {
    test(
      'refresh $mode failure clears session and never replays old refresh',
      () async {
        var fail = false, refreshes = 0;
        final h = Harness((request) async {
          if (request.url.path.endsWith('/login')) return ok(tokenData(0));
          if (request.url.path.endsWith('/refresh')) {
            ++refreshes;
            if (mode == 'network') throw const SocketException('synthetic');
            if (mode == 'server') return http.Response('unavailable', 503);
            return denied();
          }
          return fail ? denied() : ok(identityData());
        });
        addTearDown(h.dispose);
        await h.session.restore();
        await h.session.login('fixture@example.invalid', 'password');
        fail = true;
        await expectLater(
          h.session.authorized(h.repository.me),
          throwsA(isA<ApiFailure>()),
        );
        await expectLater(
          h.session.authorized(h.repository.me),
          throwsA(isA<ApiFailure>()),
        );
        expect(refreshes, 1);
        expect(h.store.bundle, isNull);
        expect(h.session.state, SessionState.anonymous);
      },
    );
  }
  test(
    'expired refresh is not sent; interrupted rotation is not restored',
    () async {
      var calls = 0;
      final store = MemoryStore()
        ..bundle = TokenBundle.fromApi(tokenData(0), clock);
      final h = Harness((_) async {
        ++calls;
        return ok(identityData());
      }, store: store);
      addTearDown(h.dispose);
      h.time = clock.add(const Duration(days: 15));
      await h.session.restore();
      expect(calls, 0);
      expect(store.bundle, isNull);
      store.pending = true;
      await h.session.restore();
      expect(calls, 0);
      expect(h.session.state, SessionState.anonymous);
    },
  );
  test('storage failure before rotation prevents network refresh', () async {
    var calls = 0;
    final store = MemoryStore()
      ..bundle = TokenBundle.fromApi(
        tokenData(0),
        clock.subtract(const Duration(minutes: 11)),
      )
      ..failPending = true;
    final h = Harness((_) async {
      ++calls;
      return ok(tokenData(1));
    }, store: store);
    addTearDown(h.dispose);
    await h.session.restore();
    expect(calls, 0);
    expect(store.bundle, isNull);
    expect(h.session.state, SessionState.anonymous);
  });
  test('save failure after rotation clears old credentials', () async {
    var refreshes = 0;
    final store = MemoryStore()
      ..bundle = TokenBundle.fromApi(
        tokenData(0),
        clock.subtract(const Duration(minutes: 11)),
      )
      ..failSave = true;
    final h = Harness((_) async {
      ++refreshes;
      return ok(tokenData(1));
    }, store: store);
    addTearDown(h.dispose);
    await h.session.restore();
    expect(refreshes, 1);
    expect(store.bundle, isNull);
    expect(h.session.state, SessionState.anonymous);
  });
  for (final failure in [false, true]) {
    test(
      'logout clears local credentials on server ${failure ? 'failure' : 'success'}',
      () async {
        var logoutCalls = 0;
        final h = Harness((request) async {
          if (request.url.path.endsWith('/login')) return ok(tokenData(0));
          if (request.url.path.endsWith('/logout')) {
            ++logoutCalls;
            expect(request.body, '{}');
            if (failure) throw const SocketException('synthetic');
            return ok({'revoked': true});
          }
          return ok(identityData());
        });
        addTearDown(h.dispose);
        await h.session.restore();
        await h.session.login('fixture@example.invalid', 'password');
        await h.session.logout();
        expect(logoutCalls, 1);
        expect(h.store.bundle, isNull);
        expect(h.session.identity, isNull);
        expect(h.session.state, SessionState.anonymous);
        if (failure) {
          expect(h.session.message, contains('could not be confirmed'));
        }
      },
    );
  }
  test(
    'logout all sends current_password and does not create a new session',
    () async {
      var logins = 0, allCalls = 0;
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) {
          ++logins;
          return ok(tokenData(0));
        }
        if (request.url.path.endsWith('/logout-all')) {
          ++allCalls;
          expect(jsonDecode(request.body), {
            'current_password': 'current password',
          });
          return ok({'revoked': true});
        }
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      await h.session.logout(currentPassword: 'current password');
      expect(logins, 1);
      expect(allCalls, 1);
      expect(h.store.bundle, isNull);
      expect(h.session.state, SessionState.anonymous);
    },
  );
  test(
    'startup connection failure retains credentials but hides authenticated shell',
    () async {
      final store = MemoryStore()
        ..bundle = TokenBundle.fromApi(tokenData(0), clock);
      final h = Harness(
        (_) async => throw const SocketException('synthetic'),
        store: store,
      );
      addTearDown(h.dispose);
      await h.session.restore();
      expect(h.session.state, SessionState.startupError);
      expect(h.session.identity, isNull);
      expect(store.bundle, isNotNull);
      await h.session.logout();
      expect(store.bundle, isNull);
    },
  );
  test(
    'late identity cannot reinstall authenticated state after logout',
    () async {
      final late = Completer<http.Response>();
      var hold = false;
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/logout')) return ok({'revoked': true});
        if (hold) return late.future;
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      hold = true;
      final pending = h.session.authorized(h.repository.me);
      final assertion = expectLater(pending, throwsA(isA<ApiFailure>()));
      await h.session.logout();
      late.complete(ok(identityData()));
      await assertion;
      expect(h.session.state, SessionState.anonymous);
      expect(h.session.identity, isNull);
      expect(h.store.bundle, isNull);
    },
  );
  test(
    'late old-token 401 uses new generation without a second refresh',
    () async {
      var expire = false, oldReads = 0, refreshes = 0;
      final delayed = Completer<http.Response>();
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/refresh')) {
          ++refreshes;
          return ok(tokenData(1));
        }
        if (expire &&
            request.headers['Authorization'] ==
                'Bearer ${credential('ka1', 0)}') {
          ++oldReads;
          if (oldReads == 2) return delayed.future;
          return denied();
        }
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      expire = true;
      final first = h.session.authorized(h.repository.me);
      final second = h.session.authorized(h.repository.me);
      await first;
      delayed.complete(denied());
      await second;
      expect(refreshes, 1);
      expect(h.session.authenticated, isTrue);
    },
  );
  test(
    'login persistence failure attempts normal revocation and stays signed out',
    () async {
      var logoutCalls = 0;
      final store = MemoryStore()..failSave = true;
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/logout')) {
          ++logoutCalls;
          return ok({'revoked': true});
        }
        throw StateError('Identity should not be requested');
      }, store: store);
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      expect(logoutCalls, 1);
      expect(store.bundle, isNull);
      expect(h.session.state, SessionState.anonymous);
    },
  );
  test(
    'logout all rejection still clears local credentials and reports uncertainty',
    () async {
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) return ok(tokenData(0));
        if (request.url.path.endsWith('/logout-all')) {
          return denied(code: 'AUTHENTICATION_FAILED');
        }
        return ok(identityData());
      });
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      await h.session.logout(currentPassword: 'wrong');
      expect(h.session.state, SessionState.anonymous);
      expect(h.store.bundle, isNull);
      expect(h.session.message, contains('could not be confirmed'));
    },
  );
  test(
    'device erase failure blocks login until secure cleanup succeeds',
    () async {
      var logins = 0;
      final store = MemoryStore();
      final h = Harness((request) async {
        if (request.url.path.endsWith('/login')) {
          ++logins;
          return ok(tokenData(0));
        }
        if (request.url.path.endsWith('/logout')) return ok({'revoked': true});
        return ok(identityData());
      }, store: store);
      addTearDown(h.dispose);
      await h.session.restore();
      await h.session.login('fixture@example.invalid', 'password');
      store.failClear = true;
      await h.session.logout();
      expect(h.session.storageBlocked, isTrue);
      await h.session.login('fixture@example.invalid', 'password');
      expect(logins, 1);
      store.failClear = false;
      await h.session.retrySecureCleanup();
      expect(h.session.storageBlocked, isFalse);
      expect(store.bundle, isNull);
    },
  );
}
