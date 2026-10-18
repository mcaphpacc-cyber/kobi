import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../core/errors/api_failure.dart';
import '../../core/storage/session_store.dart';
import '../account/account_identity.dart';
import 'auth_repository.dart';
import 'token_bundle.dart';

enum SessionState {
  restoring,
  anonymous,
  authenticating,
  authenticated,
  checking,
  startupError,
  signingOut,
}

/// The only owner of tokens/refresh. No widgets or background workers rotate.
class SessionController extends ChangeNotifier {
  SessionController(this.repository, this.store, {DateTime Function()? now})
    : now = now ?? DateTime.now;
  final AuthRepository repository;
  final SessionStore store;
  final DateTime Function() now;
  SessionState state = SessionState.restoring;
  AccountIdentity? identity;
  String? message;
  ApiFailure? error;
  int retryAfterSeconds = 0;
  DateTime? _retryAt;
  Timer? _cooldownTimer;
  TokenBundle? _bundle;
  Future<void>? _refreshFlight;
  Future<void> _storageTail = Future<void>.value();
  int _epoch = 0;
  bool _disposed = false;
  bool _restoreBusy = false;
  bool _storageBlocked = false;
  bool get busy => {
    SessionState.restoring,
    SessionState.authenticating,
    SessionState.checking,
    SessionState.signingOut,
  }.contains(state);
  bool get authenticated => state == SessionState.authenticated;
  bool get storageBlocked => _storageBlocked;
  void _emit() {
    if (!_disposed) notifyListeners();
  }

  void _check(int epoch) {
    if (_disposed || epoch != _epoch) {
      throw const ApiFailure(
        FailureKind.cancelled,
        'The operation was cancelled.',
      );
    }
  }

  // Serializing storage also prevents a late old-account write undoing sign-out.
  Future<void> _store(Future<void> Function() action, {int? epoch}) {
    final run = _storageTail.then((_) async {
      if (epoch != null) _check(epoch);
      await action();
      if (epoch != null) _check(epoch);
    });
    _storageTail = run.then<void>((_) {}, onError: (Object _, StackTrace _) {});
    return run;
  }

  ApiFailure _failure(Object failure) => failure is ApiFailure
      ? failure
      : const ApiFailure(
          FailureKind.server,
          'Unable to complete the request. Please try again.',
        );
  void _setError(ApiFailure failure) {
    _cooldownTimer?.cancel();
    error = failure;
    message = failure.message;
    _retryAt = failure.retryAfter == null
        ? null
        : now().add(Duration(seconds: failure.retryAfter!));
    retryAfterSeconds = failure.retryAfter ?? 0;
    if (retryAfterSeconds > 0) {
      _cooldownTimer = Timer(Duration(seconds: retryAfterSeconds), () {
        _retryAt = null;
        retryAfterSeconds = 0;
        _emit();
      });
    }
  }

  bool get retryAllowed => _retryAt == null || !now().isBefore(_retryAt!);

  Future<void> restore() async {
    if (_restoreBusy || busy && state != SessionState.restoring) return;
    _restoreBusy = true;
    final epoch = _epoch;
    state = SessionState.restoring;
    error = null;
    message = null;
    _emit();
    try {
      await _storageTail;
      _check(epoch);
      final restored = _bundle ?? await store.read();
      _check(epoch);
      _bundle = restored;
      if (restored == null) {
        state = SessionState.anonymous;
        return;
      }
      final verified = await authorized(repository.me);
      _check(epoch);
      identity = verified;
      state = SessionState.authenticated;
    } catch (failure) {
      if (epoch != _epoch || _disposed) return;
      final safe = _failure(failure);
      if (_bundle == null ||
          safe.kind == FailureKind.storage ||
          safe.kind == FailureKind.authentication) {
        await _end(epoch, safe.message);
      } else {
        // Network failure is not proof of logout. Hide identity until verified.
        identity = null;
        state = SessionState.startupError;
        _setError(safe);
      }
    } finally {
      _restoreBusy = false;
      _emit();
    }
  }

  Future<void> login(String email, String password) async {
    if (busy ||
        _storageBlocked ||
        !retryAllowed ||
        state != SessionState.anonymous) {
      return;
    }
    final epoch = ++_epoch;
    state = SessionState.authenticating;
    identity = null;
    error = null;
    message = null;
    _emit();
    TokenBundle? issued;
    try {
      // Verify local cleanup is possible before creating a new server family.
      await _store(store.clear, epoch: epoch);
      final tokens = await repository.login(email, password);
      issued = tokens;
      _check(epoch);
      await _store(() => store.save(tokens), epoch: epoch);
      _bundle = tokens;
      final verified = await authorized(repository.me);
      _check(epoch);
      identity = verified;
      state = SessionState.authenticated;
    } catch (failure) {
      if (epoch != _epoch || _disposed) return;
      final safe = _failure(failure);
      // If issuance succeeded but startup failed, attempt normal revocation once.
      final tokens = _bundle ?? issued;
      if (tokens != null && tokens.accessUsable(now().toUtc())) {
        try {
          await repository.logout(tokens.accessToken);
        } catch (_) {
          /* Outcome remains unconfirmed. */
        }
      }
      await _end(epoch, safe.message);
      if (!_storageBlocked) _setError(safe);
    } finally {
      _emit();
    }
  }

  Future<T> authorized<T>(Future<T> Function(String access) operation) async {
    final epoch = _epoch;
    _check(epoch);
    if (_bundle == null || state == SessionState.signingOut) {
      throw ApiFailure.expired;
    }
    if (_refreshFlight != null) await _refreshFlight;
    _check(epoch);
    if (!_bundle!.accessUsable(now().toUtc())) await _refresh(epoch);
    _check(epoch);
    final used = _bundle!;
    try {
      final result = await operation(used.accessToken);
      _check(epoch);
      return result;
    } on ApiFailure catch (failure) {
      _check(epoch);
      if (failure.status != 401) rethrow;
      if (identical(_bundle, used)) {
        await _refresh(epoch);
      } else if (_refreshFlight != null) {
        await _refreshFlight;
      }
      _check(epoch);
      final retryBundle = _bundle;
      if (retryBundle == null) throw ApiFailure.expired;
      try {
        final result = await operation(retryBundle.accessToken);
        _check(epoch);
        return result;
      } on ApiFailure catch (retryFailure) {
        if (epoch == _epoch && retryFailure.status == 401) {
          await _end(epoch, ApiFailure.expired.message);
        }
        rethrow;
      }
    }
  }

  Future<void> _refresh(int epoch) {
    if (_refreshFlight != null) return _refreshFlight!;
    final completer = Completer<void>();
    _refreshFlight = completer.future;
    unawaited(() async {
      try {
        await _rotate(epoch);
        completer.complete();
      } catch (failure, stack) {
        completer.completeError(failure, stack);
      } finally {
        _refreshFlight = null;
      }
    }());
    return completer.future;
  }

  Future<void> _rotate(int epoch) async {
    final previous = _bundle;
    try {
      _check(epoch);
      if (previous == null || !previous.refreshUsable(now().toUtc())) {
        throw ApiFailure.expired;
      }
      await _store(store.markRotationPending, epoch: epoch);
      final next = await repository.refresh(previous.refreshToken);
      _check(epoch);
      if (next.sessionId != previous.sessionId ||
          next.accessToken == previous.accessToken ||
          next.refreshToken == previous.refreshToken) {
        throw ApiFailure.malformed;
      }
      await _store(() => store.save(next), epoch: epoch);
      _check(epoch);
      _bundle = next;
    } catch (failure) {
      if (epoch == _epoch && !_disposed) {
        final safe = _failure(failure);
        await _end(
          epoch,
          safe.kind == FailureKind.authentication
              ? ApiFailure.expired.message
              : '${safe.message} Please sign in again; refresh will not be retried.',
        );
      }
      throw _failure(failure);
    }
  }

  Future<void> refreshIdentity() async {
    if (!authenticated || !retryAllowed) return;
    final epoch = _epoch;
    state = SessionState.checking;
    error = null;
    message = null;
    _emit();
    try {
      final verified = await authorized(repository.me);
      _check(epoch);
      identity = verified;
    } catch (failure) {
      if (epoch == _epoch) _setError(_failure(failure));
    } finally {
      if (epoch == _epoch) {
        state = SessionState.authenticated;
        _emit();
      }
    }
  }

  Future<void> logout({String? currentPassword}) async {
    if (busy && state != SessionState.restoring ||
        state == SessionState.signingOut) {
      return;
    }
    // Reuse any in-flight refresh, but invalidate UI requests immediately.
    final pendingRefresh = _refreshFlight;
    final epoch = _epoch;
    state = SessionState.signingOut;
    identity = null;
    error = null;
    message = null;
    _emit();
    var confirmed = false;
    try {
      if (pendingRefresh != null) await pendingRefresh;
      _check(epoch);
      if (_bundle != null && !_bundle!.accessUsable(now().toUtc())) {
        await _refresh(epoch);
      }
      _check(epoch);
      final tokens = _bundle;
      if (tokens != null) {
        // No automatic retry of this state-changing request.
        await repository.logout(
          tokens.accessToken,
          currentPassword: currentPassword,
        );
        confirmed = true;
      }
    } catch (_) {
      /* Local exit is mandatory; remote revocation may be unconfirmed. */
    } finally {
      if (epoch == _epoch) {
        await _end(
          epoch,
          confirmed
              ? (currentPassword == null
                    ? 'Signed out.'
                    : 'All API sessions were signed out. Browser sessions are unchanged.')
              : 'Signed out on this device. Server sign-out could not be confirmed.',
        );
      }
    }
  }

  Future<void> _end(int epoch, String notice) async {
    if (epoch != _epoch || _disposed) return;
    ++_epoch;
    _cooldownTimer?.cancel();
    _bundle = null;
    identity = null;
    state = SessionState.anonymous;
    _storageBlocked = true;
    message = notice;
    error = null;
    _retryAt = null;
    _emit();
    try {
      await _store(store.clear);
      _storageBlocked = false;
    } catch (_) {
      _storageBlocked = true;
      message =
          'Signed out locally, but device credentials could not be erased. Unlock the device and retry secure cleanup before signing in.';
      error = ApiFailure.storage;
    }
    _emit();
  }

  Future<void> retrySecureCleanup() async {
    try {
      await _store(store.clear);
      _storageBlocked = false;
      error = null;
      message = 'Device credentials cleared.';
    } catch (_) {
      _storageBlocked = true;
      _setError(ApiFailure.storage);
    }
    _emit();
  }

  @override
  void dispose() {
    _cooldownTimer?.cancel();
    _disposed = true;
    ++_epoch;
    _bundle = null;
    identity = null;
    super.dispose();
  }
}
