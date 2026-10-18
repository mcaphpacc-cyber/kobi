import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:http/http.dart' as http;

import '../../app/environment.dart';
import '../errors/api_failure.dart';

class ApiEnvelope {
  const ApiEnvelope(this.data, this.meta);
  final Object data;
  final Map<String, dynamic> meta;
}

class ApiClient {
  ApiClient(
    this.environment,
    this._client, {
    this.timeout = const Duration(seconds: 30),
  });
  final Environment environment;
  final http.Client _client;
  final Duration timeout;

  Future<Map<String, dynamic>> request(
    String method,
    String path, {
    Map<String, Object?>? body,
    String? accessToken,
  }) async {
    final result = await requestEnvelope(
      method,
      path,
      body: body,
      accessToken: accessToken,
    );
    if (result.data is! Map<String, dynamic>) throw ApiFailure.malformed;
    return result.data as Map<String, dynamic>;
  }

  Future<ApiEnvelope> requestEnvelope(
    String method,
    String path, {
    Map<String, Object?>? body,
    String? accessToken,
    Map<String, String>? query,
  }) async {
    // Relative paths only. This transport never follows redirects or sends cookies.
    if (!path.startsWith('/') ||
        path.startsWith('//') ||
        path.contains('..') ||
        path.contains('?') ||
        path.contains('#') ||
        path.contains('\\') ||
        !{'GET', 'POST'}.contains(method)) {
      throw ApiFailure.malformed;
    }
    final checkoff = RegExp(
      r'^/health-profiles/[1-9][0-9]{0,9}/medication-schedules/[1-9][0-9]{0,9}/checkoff$',
    ).hasMatch(path);
    if (checkoff &&
        (method != 'POST' ||
            accessToken == null ||
            query?.isNotEmpty == true ||
            body?.length != 1 ||
            !{'taken', 'skipped'}.contains(body?['state']))) {
      throw ApiFailure.malformed;
    }
    if (accessToken != null &&
        (method == 'POST' &&
                !{'/auth/logout', '/auth/logout-all'}.contains(path) &&
                !checkoff ||
            path == '/auth/login' ||
            path == '/auth/refresh' ||
            path.startsWith('/diseases') ||
            path.startsWith('/substances'))) {
      throw ApiFailure.malformed;
    }
    final abort = Completer<void>();
    final request =
        http.AbortableRequest(
            method,
            environment.baseUrl.replace(
              path: '${environment.baseUrl.path}$path',
              queryParameters: query,
            ),
            abortTrigger: abort.future,
          )
          ..followRedirects = false
          ..headers['Accept'] = 'application/json';
    if (accessToken != null) {
      request.headers['Authorization'] = 'Bearer $accessToken';
    }
    if (method == 'POST') {
      request.headers['Content-Type'] = 'application/json';
      request.body = jsonEncode(body ?? <String, Object?>{});
    }
    try {
      return await _send(request).timeout(
        timeout,
        onTimeout: () {
          if (!abort.isCompleted) abort.complete();
          throw const ApiFailure(
            FailureKind.timeout,
            'The request timed out. Please try again.',
          );
        },
      );
    } on ApiFailure {
      rethrow;
    } on SocketException {
      throw const ApiFailure(
        FailureKind.network,
        'Unable to connect. Check your connection and try again.',
      );
    } on HandshakeException {
      throw const ApiFailure(
        FailureKind.network,
        'A secure connection could not be established.',
      );
    } on http.ClientException {
      throw const ApiFailure(
        FailureKind.network,
        'Unable to connect securely. Please try again.',
      );
    } on FormatException {
      throw ApiFailure.malformed;
    } finally {
      if (!abort.isCompleted) abort.complete();
    }
  }

  Future<ApiEnvelope> _send(http.BaseRequest request) async {
    final response = await _client.send(request);
    final bytes = BytesBuilder(copy: false);
    await for (final chunk in response.stream) {
      bytes.add(chunk);
      if (bytes.length > 262144) throw ApiFailure.malformed;
    }
    if (!(response.headers['content-type'] ?? '').toLowerCase().startsWith(
      'application/json',
    )) {
      throw ApiFailure.malformed;
    }
    final decoded = jsonDecode(utf8.decode(bytes.takeBytes()));
    if (decoded is! Map<String, dynamic> ||
        decoded['meta'] is! Map<String, dynamic> ||
        (decoded['meta'] as Map<String, dynamic>)['api_version'] != 'v1') {
      throw ApiFailure.malformed;
    }
    if (response.statusCode == 200 &&
        decoded['success'] == true &&
        (decoded['data'] is Map<String, dynamic> || decoded['data'] is List) &&
        !decoded.containsKey('error')) {
      return ApiEnvelope(
        decoded['data'] as Object,
        decoded['meta'] as Map<String, dynamic>,
      );
    }
    if (response.statusCode < 400 ||
        decoded['success'] != false ||
        decoded['error'] is! Map<String, dynamic> ||
        decoded.containsKey('data')) {
      throw ApiFailure.malformed;
    }
    final error = decoded['error'] as Map<String, dynamic>;
    if (error['code'] is! String || error['message'] is! String) {
      throw ApiFailure.malformed;
    }
    final status = response.statusCode;
    final retry = int.tryParse(response.headers['retry-after'] ?? '');
    throw ApiFailure(
      status == 401
          ? FailureKind.authentication
          : {400, 413, 415, 422}.contains(status)
          ? FailureKind.validation
          : FailureKind.server,
      status == 401 && error['code'] == 'AUTHENTICATION_FAILED'
          ? 'Unable to sign in with these credentials.'
          : status == 401
          ? ApiFailure.expired.message
          : status == 429
          ? 'Too many requests. Please wait before trying again.'
          : {400, 413, 415, 422}.contains(status)
          ? 'Check the submitted information and try again.'
          : 'The service is unavailable. Please try again later.',
      status: status,
      code: _safeCode(error['code'] as String),
      retryAfter: retry != null && retry > 0 ? retry : null,
    );
  }

  String? _safeCode(String code) =>
      RegExp(r'^[A-Z_]{1,40}$').hasMatch(code) ? code : null;
  void close() => _client.close();
}
