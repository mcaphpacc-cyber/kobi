import '../../core/errors/api_failure.dart';
import '../../core/network/api_client.dart';
import '../health/health_models.dart';
import '../health/health_repository.dart';
import '../health/health_state.dart';
import '../health/paged_state.dart';
import 'schedule_models.dart';

class DailyHealthRepository {
  DailyHealthRepository(this.health);
  final HealthRepository health;
  Future<ApiEnvelope> _get(String profileId, int page, String suffix) async {
    // Profile ID comes only from the validated HealthScope, never route arguments.
    if (!RegExp(r'^[1-9][0-9]{0,9}$').hasMatch(profileId) ||
        (profileId.length == 10 && profileId.compareTo('2147483647') > 0)) {
      throw ApiFailure.malformed;
    }
    return health.session.authorized(
      (token) => health.api.requestEnvelope(
        'GET',
        '/health-profiles/$profileId/medication-schedules$suffix',
        accessToken: token,
        query: {'page': '$page', 'page_size': '20'},
      ),
    );
  }

  Future<ReadPage<MedicationSlot>> schedules(String id, int page) async {
    final envelope = await _get(id, page, '');
    return ReadPage.fromApi(
      envelope.data,
      envelope.meta,
      MedicationSlot.fromApi,
    );
  }

  Future<DuePage> dueToday(String id, int page) async {
    final envelope = await _get(id, page, '/due-today');
    final capability = envelope.meta['can_record_checkoffs'];
    if (capability != null && capability is! bool) throw ApiFailure.malformed;
    final rows = ReadPage.fromApi(
      envelope.data,
      envelope.meta,
      DueEntry.fromApi,
    );
    return DuePage(rows, capability == true);
  }

  Future<DueEntry> checkoff(
    String profileId,
    DueEntry entry,
    String state,
  ) async {
    for (final id in [profileId, entry.id]) {
      if (!RegExp(r'^[1-9][0-9]{0,9}$').hasMatch(id) ||
          (id.length == 10 && id.compareTo('2147483647') > 0)) {
        throw ApiFailure.malformed;
      }
    }
    if (!{'taken', 'skipped'}.contains(state)) throw ApiFailure.malformed;
    final data = await health.session.authorized(
      (token) => health.api.request(
        'POST',
        '/health-profiles/$profileId/medication-schedules/${entry.id}/checkoff',
        accessToken: token,
        body: {'state': state},
      ),
    );
    final result = DueEntry.fromApi(data);
    if (result.id != entry.id ||
        result.medicineId != entry.medicineId ||
        result.state == null) {
      throw ApiFailure.malformed;
    }
    return result;
  }
}

class DuePage extends ReadPage<DueEntry> {
  DuePage(ReadPage<DueEntry> page, this.canRecord)
    : super(page.items, page.page, page.total, page.hasMore);
  final bool canRecord;
}

class DueTodayState extends PagedState<DueEntry> {
  DueTodayState(this.health, DailyHealthRepository repository)
    : super(
        (page) async {
          final result = await repository.dueToday(health.profile.id, page);
          if (result.canRecord && health.medicines.value == null) {
            await health.loadMedicines();
          }
          return result;
        },
        (item) => '${item.id}:${item.scheduledOn.display}',
        onFailure: health.profileFailure,
        restartIf: (previous, incoming) {
          final days = {
            for (final item in previous)
              item.timezone: item.scheduledOn.display,
          };
          return incoming.isEmpty ||
              incoming.any(
                (item) =>
                    days.containsKey(item.timezone) &&
                    days[item.timezone] != item.scheduledOn.display,
              );
        },
      ) {
    _repository = repository;
  }
  final HealthScope health;
  late final DailyHealthRepository _repository;
  final _actions = <String, LoadState<DueEntry>>{};
  final _pending = <String>{}, _blocked = <String>{};
  final notices = <String, String>{};
  final _confirmed = <String, DueEntry>{};
  bool _needsRefresh = false;
  bool canRecord = false, _alive = true;
  int _reads = 0;

  String? medicineName(DueEntry entry) {
    for (final medicine in health.medicines.value ?? <PersonalMedicine>[]) {
      if (medicine.id == entry.medicineId && medicine.brand.trim().isNotEmpty) {
        return medicine.brand;
      }
    }
    return null;
  }

  bool pending(DueEntry entry) => _pending.contains(entry.id);
  bool canAct(DueEntry entry) =>
      _alive &&
      canRecord &&
      !_blocked.contains(entry.id) &&
      !pending(entry) &&
      (_actions[entry.id]?.retryAllowed ?? true) &&
      medicineName(entry) != null;
  String? actionError(DueEntry entry) {
    final failure = _actions[entry.id]?.error;
    if (failure == null) return null;
    if (failure.status == 403) {
      return 'Recording is no longer permitted. Refresh Due Today.';
    }
    if (failure.status == 404 || failure.status == 409) {
      return 'This occurrence is no longer available. Refresh Due Today.';
    }
    return '${failure.message} Recording was not confirmed. Refresh Due Today before trying again.';
  }

  @override
  Future<void> load({bool more = false}) async {
    if (!_alive ||
        _pending.isNotEmpty ||
        request.loading ||
        !request.retryAllowed) {
      return;
    }
    final read = ++_reads;
    if (!more) canRecord = false;
    await super.load(more: more);
    if (!_alive || read != _reads) return;
    if (request.value case final DuePage value) {
      canRecord = value.canRecord;
      _needsRefresh = false;
      _confirmed.clear();
      _blocked.clear();
      notices.clear();
      for (final action in _actions.values) {
        action.clear(keepCooldown: true);
      }
      notifyListeners();
    }
  }

  Future<void> record(DueEntry entry, String state) async {
    if (!canAct(entry) || !items.any((item) => identical(item, entry))) return;
    ++_reads;
    invalidatePendingLoad();
    _pending.add(entry.id);
    notices.remove(entry.id);
    final action = _actions.putIfAbsent(
      entry.id,
      () => LoadState<DueEntry>()..addListener(notifyListeners),
    );
    notifyListeners();
    await action.load(
      () => _repository.checkoff(health.profile.id, entry, state),
    );
    if (!_alive) return;
    // No GET may begin while a write is pending; older GETs were invalidated above.
    if (action.value case final DueEntry confirmed) {
      _confirmed[entry.id] = confirmed;
      final rollover =
          confirmed.scheduledOn.display != entry.scheduledOn.display ||
          confirmed.timezone != entry.timezone;
      if (rollover || _needsRefresh) {
        _needsRefresh = true;
        // Keep other server-confirmed responses, never older unconfirmed pages.
        _confirmed.removeWhere(
          (id, item) =>
              item.timezone == confirmed.timezone &&
              item.scheduledOn.display != confirmed.scheduledOn.display,
        );
        items = _confirmed.values.toList();
        page = 0;
        total = items.length;
        hasMore = false;
        canRecord = false;
        notices[entry.id] =
            'Recorded for ${confirmed.scheduledOn.display}. The schedule date changed; refresh Due Today.';
      } else {
        items = [
          for (final item in items)
            if (item.id == entry.id) confirmed else item,
        ];
        notices[entry.id] = 'Recorded by server.';
      }
    } else {
      _blocked.add(entry.id);
      if (action.error?.status == 403) canRecord = false;
      if (action.error?.status == 404) {
        // A missing child alone does not prove loss of profile access.
        try {
          await health.repository.profile(health.profile.id);
        } on ApiFailure catch (failure) {
          if (_alive) health.profileFailure(failure);
        }
      }
    }
    if (!_alive) return;
    _pending.remove(entry.id);
    notifyListeners();
  }

  @override
  void dispose() {
    if (!_alive) return;
    _alive = false;
    ++_reads;
    for (final action in _actions.values) {
      action.removeListener(notifyListeners);
      action.dispose();
    }
    _pending.clear();
    notices.clear();
    super.dispose();
  }
}

class DailyHealthScope {
  DailyHealthScope(this.health) {
    final scope = health;
    final repository = DailyHealthRepository(scope.repository);
    schedules = PagedState(
      (page) => repository.schedules(scope.profile.id, page),
      (item) => item.id,
      onFailure: scope.profileFailure,
    );
    due = DueTodayState(scope, repository);
  }
  final HealthScope health;
  late final PagedState<MedicationSlot> schedules;
  late final DueTodayState due;
  void dispose() {
    schedules.dispose();
    due.dispose();
  }
}
