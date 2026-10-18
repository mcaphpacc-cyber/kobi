enum FailureKind {
  authentication,
  validation,
  network,
  timeout,
  server,
  protocol,
  storage,
  cancelled,
}

/// Deliberately contains no raw exception, response, URL, password or token.
class ApiFailure implements Exception {
  const ApiFailure(
    this.kind,
    this.message, {
    this.status,
    this.code,
    this.retryAfter,
  });
  final FailureKind kind;
  final String message;
  final int? status;
  final String? code;
  final int? retryAfter;
  static const expired = ApiFailure(
    FailureKind.authentication,
    'Your session has ended. Please sign in again.',
  );
  static const malformed = ApiFailure(
    FailureKind.protocol,
    'The service returned an unexpected response. Please try again later.',
  );
  static const storage = ApiFailure(
    FailureKind.storage,
    'Secure storage is unavailable. Please unlock your device and try again.',
  );
  @override
  String toString() => 'ApiFailure(${kind.name}, status: $status)';
}
