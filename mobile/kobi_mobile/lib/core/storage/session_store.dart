import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

import '../errors/api_failure.dart';
import '../../features/auth/token_bundle.dart';

abstract interface class SessionStore {
  Future<TokenBundle?> read();
  Future<void> save(TokenBundle bundle);
  Future<void> markRotationPending();
  Future<void> clear();
}

class SecureSessionStore implements SessionStore {
  SecureSessionStore(this.scope, {FlutterSecureStorage? storage})
    : _storage =
          storage ??
          const FlutterSecureStorage(
            aOptions: AndroidOptions(
              resetOnError: false,
              migrateOnAlgorithmChange: false,
            ),
            iOptions: IOSOptions(
              accessibility: KeychainAccessibility.unlocked_this_device,
              synchronizable: false,
            ),
          );
  final String scope;
  final FlutterSecureStorage _storage;
  static const _key = 'kobi.session.v1';

  @override
  Future<TokenBundle?> read() async {
    try {
      final raw = await _storage.read(key: _key);
      if (raw == null) return null;
      final value = jsonDecode(raw);
      if (value is! Map<String, dynamic> ||
          value['version'] != 1 ||
          value['scope'] != scope ||
          value['state'] != 'ready') {
        await clear();
        return null;
      }
      final bundle = value['bundle'];
      if (bundle is! Map<String, dynamic>) throw ApiFailure.malformed;
      return TokenBundle.fromStorage(bundle);
    } catch (_) {
      throw ApiFailure.storage;
    }
  }

  Future<void> _write(Map<String, Object?> value) async {
    try {
      final encoded = jsonEncode(value);
      await _storage.write(key: _key, value: encoded);
      if (defaultTargetPlatform == TargetPlatform.android) {
        // The pinned plugin uses apply(); wait for its encrypted disk write.
        final flushed = await const MethodChannel(
          'org.kobi.mobile/secure_session',
        ).invokeMethod<bool>('flush');
        if (flushed != true) throw ApiFailure.storage;
      }
      if (await _storage.read(key: _key) != encoded) throw ApiFailure.storage;
    } catch (_) {
      throw ApiFailure.storage;
    }
  }

  @override
  Future<void> save(TokenBundle bundle) => _write({
    'version': 1,
    'scope': scope,
    'state': 'ready',
    'bundle': bundle.toStorage(),
  });
  // Invalidate the persisted one-use token BEFORE a refresh can reach the server.
  // A process death after rotation must never restore/replay the consumed token.
  @override
  Future<void> markRotationPending() =>
      _write({'version': 1, 'scope': scope, 'state': 'rotation_pending'});
  @override
  Future<void> clear() async {
    await _write({'version': 1, 'scope': scope, 'state': 'signed_out'});
    try {
      await _storage.delete(key: _key);
    } catch (_) {
      // Verified signed-out tombstone already contains no credentials.
      return;
    }
  }
}
