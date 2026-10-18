import '../../core/errors/api_failure.dart';

class AccountIdentity {
  const AccountIdentity(this.id, this.email, this.status, this.emailVerified);
  final String id, email, status;
  final bool emailVerified;
  factory AccountIdentity.fromApi(Map<String, dynamic> data) {
    if (data['id'] is! String ||
        !RegExp(r'^[1-9][0-9]*$').hasMatch(data['id'] as String) ||
        data['email'] is! String ||
        data['status'] != 'active' ||
        data['email_verified'] != true) {
      throw ApiFailure.malformed;
    }
    return AccountIdentity(
      data['id'] as String,
      data['email'] as String,
      data['status'] as String,
      true,
    );
  }
  @override
  String toString() => 'AccountIdentity(redacted)';
}
