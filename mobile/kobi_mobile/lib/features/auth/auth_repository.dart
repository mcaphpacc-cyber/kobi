import '../../core/network/api_client.dart';
import '../../core/errors/api_failure.dart';
import '../account/account_identity.dart';
import 'token_bundle.dart';

class AuthRepository {
  AuthRepository(this.api, {DateTime Function()? now})
    : now = now ?? DateTime.now;
  final ApiClient api;
  final DateTime Function() now;
  Future<TokenBundle> login(String email, String password) async {
    final started = now().toUtc();
    return TokenBundle.fromApi(
      await api.request(
        'POST',
        '/auth/login',
        body: {
          'email': email.trim(),
          'password': password,
          'device_label': 'KOBI Mobile',
        },
      ),
      started,
    );
  }

  Future<TokenBundle> refresh(String refreshToken) async {
    final started = now().toUtc();
    return TokenBundle.fromApi(
      await api.request(
        'POST',
        '/auth/refresh',
        body: {'refresh_token': refreshToken},
      ),
      started,
    );
  }

  Future<AccountIdentity> me(String access) async => AccountIdentity.fromApi(
    await api.request('GET', '/me', accessToken: access),
  );
  Future<void> logout(String access, {String? currentPassword}) async {
    final data = await api.request(
      'POST',
      currentPassword == null ? '/auth/logout' : '/auth/logout-all',
      accessToken: access,
      body: currentPassword == null
          ? {}
          : {'current_password': currentPassword},
    );
    if (data['revoked'] != true) throw ApiFailure.malformed;
  }
}
