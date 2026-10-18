import '../../core/errors/api_failure.dart';

class TokenBundle {
  TokenBundle._(
    this.accessToken,
    this.refreshToken,
    this.accessExpiresAt,
    this.refreshExpiresAt,
    this.sessionExpiresAt,
    this.sessionId,
  );
  final String accessToken, refreshToken, sessionId;
  final DateTime accessExpiresAt, refreshExpiresAt, sessionExpiresAt;

  factory TokenBundle.fromApi(
    Map<String, dynamic> data,
    DateTime requestStarted,
  ) {
    final seconds = data['expires_in'];
    if (data['token_type'] != 'Bearer' ||
        seconds is! int ||
        seconds < 1 ||
        seconds > 600) {
      throw ApiFailure.malformed;
    }
    return TokenBundle._parse(
      data,
      requestStarted.toUtc().add(Duration(seconds: seconds)).toIso8601String(),
    );
  }
  factory TokenBundle.fromStorage(Map<String, dynamic> data) =>
      TokenBundle._parse(data, data['access_expires_at']);

  static TokenBundle _parse(Map<String, dynamic> data, Object? accessDate) {
    String token(String key, String prefix) {
      final value = data[key];
      if (value is! String ||
          !RegExp(
            '^${prefix}_[A-Za-z0-9_-]{42}[AEIMQUYcgkosw048]'
            r'$',
          ).hasMatch(value)) {
        throw ApiFailure.malformed;
      }
      return value;
    }

    DateTime date(Object? value) {
      if (value is! String ||
          !RegExp(
            r'^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?Z$',
          ).hasMatch(value)) {
        throw ApiFailure.malformed;
      }
      final parsed = DateTime.tryParse(value);
      if (parsed == null ||
          !parsed.isUtc ||
          parsed.toIso8601String().substring(0, 19) != value.substring(0, 19)) {
        throw ApiFailure.malformed;
      }
      return parsed;
    }

    final id = data['session_id'];
    if (id is! String || !RegExp(r'^[a-f0-9]{32}$').hasMatch(id)) {
      throw ApiFailure.malformed;
    }
    final access = date(accessDate),
        refresh = date(data['refresh_expires_at']),
        absolute = date(data['session_expires_at']);
    if (access.isAfter(absolute) || refresh.isAfter(absolute)) {
      throw ApiFailure.malformed;
    }
    return TokenBundle._(
      token('access_token', 'ka1'),
      token('refresh_token', 'kr1'),
      access,
      refresh,
      absolute,
      id,
    );
  }

  bool accessUsable(DateTime now) =>
      now.add(const Duration(seconds: 15)).isBefore(accessExpiresAt) &&
      now.isBefore(sessionExpiresAt);
  bool refreshUsable(DateTime now) =>
      now.isBefore(refreshExpiresAt) && now.isBefore(sessionExpiresAt);
  Map<String, Object?> toStorage() => {
    'access_token': accessToken,
    'refresh_token': refreshToken,
    'session_id': sessionId,
    'access_expires_at': accessExpiresAt.toUtc().toIso8601String(),
    'refresh_expires_at': refreshExpiresAt.toUtc().toIso8601String(),
    'session_expires_at': sessionExpiresAt.toUtc().toIso8601String(),
  };
  @override
  String toString() => 'TokenBundle(redacted)';
}
