import '../auth/session_controller.dart';
import '../health/health_state.dart';
import '../health/paged_state.dart';
import '../../core/errors/api_failure.dart';
import 'saved_models.dart';

class SavedCheckRepository {
  SavedCheckRepository(this.session);
  final SessionController session;
  Future<ReadPage<SavedCheckMetadata>> list(int page) async {
    final value = await session.authorized(
      (token) => session.repository.api.requestEnvelope(
        'GET',
        '/saved-symptom-checks',
        accessToken: token,
        query: {'page': '$page', 'page_size': '20'},
      ),
    );
    return ReadPage.fromApi(value.data, value.meta, SavedCheckMetadata.fromApi);
  }

  Future<SavedCheckDetail> detail(String id) async {
    if (!RegExp(r'^[1-9][0-9]{0,9}$').hasMatch(id) ||
        (id.length == 10 && id.compareTo('2147483647') > 0)) {
      throw ApiFailure.malformed;
    }
    final value = await session.authorized(
      (token) => session.repository.api.request(
        'GET',
        '/saved-symptom-checks/$id',
        accessToken: token,
      ),
    );
    final result = SavedCheckDetail.fromApi(value);
    if (result.metadata.id != id) throw ApiFailure.malformed;
    return result;
  }
}

/// Account-owned state, intentionally outside the selected-profile lifetime.
class SavedChecksController {
  SavedChecksController(this.repository) {
    list = PagedState(repository.list, (item) => item.id);
    repository.session.addListener(_sessionChanged);
  }
  final SavedCheckRepository repository;
  late final PagedState<SavedCheckMetadata> list;
  final detail = LoadState<SavedCheckDetail>();
  String? _detailId;
  void _sessionChanged() {
    if (repository.session.identity == null) {
      list.clear();
      detail.clear();
      _detailId = null;
    }
  }

  Future<void> open(String id) {
    if (_detailId != id) {
      _detailId = id;
      detail.clear(keepCooldown: true);
    }
    return detail.load(() => repository.detail(id));
  }

  void dispose() {
    repository.session.removeListener(_sessionChanged);
    list.dispose();
    detail.dispose();
  }
}
