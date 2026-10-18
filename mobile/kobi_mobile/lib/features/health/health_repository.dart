import '../../core/errors/api_failure.dart';
import '../../core/network/api_client.dart';
import '../auth/session_controller.dart';
import 'health_models.dart';

class HealthRepository {
  HealthRepository(this.api, this.session);
  final ApiClient api;
  final SessionController session;

  Future<ApiEnvelope> _get(String path, {Map<String, String>? query}) =>
      session.authorized(
        (token) =>
            api.requestEnvelope('GET', path, accessToken: token, query: query),
      );

  String _id(String id) {
    // These endpoints accept signed INT IDs, while DTOs retain decimal strings.
    if (!RegExp(r'^[1-9][0-9]{0,9}$').hasMatch(id) ||
        (id.length == 10 && id.compareTo('2147483647') > 0)) {
      throw ApiFailure.malformed;
    }
    return id;
  }

  String _profile(String id) => '/health-profiles/${_id(id)}';
  Future<List<HealthProfile>> profiles() async =>
      collection((await _get('/health-profiles')).data, HealthProfile.fromApi);
  Future<HealthProfile> profile(String id) async {
    final value = HealthProfile.fromApi((await _get(_profile(id))).data);
    if (value.id != id) throw ApiFailure.malformed;
    return value;
  }

  Future<MedicalSummary> summary(String id) async {
    final value = MedicalSummary.fromApi(
      (await _get('${_profile(id)}/medical-summary')).data,
    );
    if (value.profile.id != id) throw ApiFailure.malformed;
    return value;
  }

  Future<ConditionPage> conditions(String id, int page) async {
    final value = await _get(
      '${_profile(id)}/conditions',
      query: {'page': '$page', 'page_size': '20'},
    );
    final result = ConditionPage.fromApi(value.data, value.meta);
    if (result.page != page || result.pageSize != 20) {
      throw ApiFailure.malformed;
    }
    return result;
  }

  Future<MedicalCondition> condition(String profileId, String id) async {
    final value = MedicalCondition.fromApi(
      (await _get('${_profile(profileId)}/conditions/${_id(id)}')).data,
    );
    if (value.id != id) throw ApiFailure.malformed;
    return value;
  }

  Future<List<PersonalMedicine>> medicines(String id) async => collection(
    (await _get('${_profile(id)}/medications')).data,
    PersonalMedicine.fromApi,
  );
  Future<PersonalMedicine> medicine(String profileId, String id) async {
    final value = PersonalMedicine.fromApi(
      (await _get('${_profile(profileId)}/medications/${_id(id)}')).data,
    );
    if (value.id != id) throw ApiFailure.malformed;
    return value;
  }
}
