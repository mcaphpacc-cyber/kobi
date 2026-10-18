import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../core/errors/api_failure.dart';
import 'health_models.dart';
import 'health_repository.dart';

/// Ephemeral state only. Disposal/reload invalidates late asynchronous results.
class LoadState<T> extends ChangeNotifier {
  T? value;
  ApiFailure? error;
  bool loading = false, _disposed = false;
  int _generation = 0;
  DateTime? _retryAt;
  Timer? _timer;
  bool get retryAllowed =>
      _retryAt == null || !DateTime.now().isBefore(_retryAt!);
  Future<void> load(
    Future<T> Function() fetch, {
    void Function(ApiFailure)? onFailure,
  }) async {
    if (_disposed || loading || !retryAllowed) return;
    final generation = ++_generation;
    value = null;
    error = null;
    loading = true;
    notifyListeners();
    try {
      final result = await fetch();
      if (!_disposed && generation == _generation) value = result;
    } on ApiFailure catch (failure) {
      if (_disposed || generation != _generation) return;
      error = failure;
      if (failure.retryAfter case final int seconds) {
        _retryAt = DateTime.now().add(Duration(seconds: seconds));
        _timer?.cancel();
        _timer = Timer(Duration(seconds: seconds), () {
          if (!_disposed) notifyListeners();
        });
      }
      onFailure?.call(failure);
    } finally {
      if (!_disposed && generation == _generation) {
        loading = false;
        notifyListeners();
      }
    }
  }

  void clear({bool keepCooldown = false}) {
    ++_generation;
    value = null;
    error = null;
    loading = false;
    if (!keepCooldown) {
      _retryAt = null;
      _timer?.cancel();
    }
    if (!_disposed) notifyListeners();
  }

  @override
  void dispose() {
    _disposed = true;
    clear();
    super.dispose();
  }
}

class ProfileController extends ChangeNotifier {
  ProfileController(this.repository) {
    repository.session.addListener(_sessionChanged);
  }
  final HealthRepository repository;
  final list = LoadState<List<HealthProfile>>();
  final selection = LoadState<HealthProfile>();
  HealthProfile? selected;
  int revision = 0, _operation = 0;
  bool _disposed = false;
  String? notice;

  void _sessionChanged() {
    if (repository.session.identity == null) {
      choose();
      list.clear();
    }
  }

  void choose({String? message}) {
    if (_disposed) return;
    ++_operation;
    selected = null;
    ++revision;
    notice = message;
    selection.clear(keepCooldown: true);
    notifyListeners();
  }

  Future<void> refresh({bool autoSelect = true}) async {
    choose();
    final operation = _operation;
    await list.load(repository.profiles);
    if (_disposed || operation != _operation) return;
    if (autoSelect && list.value?.length == 1) await select(list.value!.single);
  }

  Future<void> select(HealthProfile profile) async {
    if (!selection.retryAllowed) return;
    choose();
    final operation = _operation;
    await selection.load(() => repository.profile(profile.id));
    if (_disposed || operation != _operation) return;
    selected = selection.value;
    if (selection.error?.status == 403 || selection.error?.status == 404) {
      list.clear();
      notice =
          'This health profile is no longer available. Refresh your profiles.';
    }
    notifyListeners();
  }

  void accessLost() {
    choose(
      message:
          'This health profile is no longer available. Refresh your profiles.',
    );
    list.clear();
  }

  @override
  void dispose() {
    _disposed = true;
    repository.session.removeListener(_sessionChanged);
    list.dispose();
    selection.dispose();
    super.dispose();
  }
}

class ConditionListState extends ChangeNotifier {
  ConditionListState(this.repository, this.profileId, this.onFailure);
  final HealthRepository repository;
  final String profileId;
  final void Function(ApiFailure) onFailure;
  final request = LoadState<ConditionPage>();
  List<MedicalCondition> items = [];
  int page = 0, total = 0, _generation = 0;
  bool hasMore = false, initialized = false, _disposed = false;
  bool get loading => request.loading;
  ApiFailure? get error => request.error;
  Future<void> load({bool more = false}) async {
    if (_disposed || loading || !request.retryAllowed || (more && !hasMore)) {
      return;
    }
    if (!more) {
      ++_generation;
      items = [];
      page = 0;
      total = 0;
      hasMore = false;
      initialized = false;
    }
    final generation = _generation;
    final next = page + 1;
    final pending = request.load(
      () => repository.conditions(profileId, next),
      onFailure: onFailure,
    );
    notifyListeners();
    await pending;
    if (_disposed || generation != _generation) return;
    if (request.value case final ConditionPage result) {
      final seen = items.map((e) => e.id).toSet();
      items = [...items, ...result.items.where((e) => seen.add(e.id))];
      page = result.page;
      total = result.total;
      hasMore = result.hasMore;
      initialized = true;
    }
    notifyListeners();
  }

  @override
  void dispose() {
    _disposed = true;
    ++_generation;
    items = [];
    page = 0;
    total = 0;
    initialized = false;
    hasMore = false;
    request.dispose();
    super.dispose();
  }
}

/// One validated profile owns every model and request. Never reused after a switch.
class HealthScope {
  HealthScope(this.profiles, this.profile) {
    conditions = ConditionListState(repository, profile.id, profileFailure);
  }
  final ProfileController profiles;
  final HealthProfile profile;
  HealthRepository get repository => profiles.repository;
  final summary = LoadState<MedicalSummary>();
  final medicines = LoadState<List<PersonalMedicine>>();
  final conditionDetail = LoadState<MedicalCondition>();
  final medicineDetail = LoadState<PersonalMedicine>();
  late final ConditionListState conditions;
  bool _disposed = false;
  String? _conditionId, _medicineId;
  Future<void> loadSummary() => summary.load(
    () => repository.summary(profile.id),
    onFailure: profileFailure,
  );
  Future<void> loadMedicines() => medicines.load(
    () => repository.medicines(profile.id),
    onFailure: profileFailure,
  );
  void profileFailure(ApiFailure failure) {
    if (!_disposed && (failure.status == 403 || failure.status == 404)) {
      profiles.accessLost();
    }
  }

  // A missing child is not proof that access to its parent has been lost.
  Future<void> _checkParent(ApiFailure failure) async {
    if (failure.status != 403 && failure.status != 404) return;
    try {
      await repository.profile(profile.id);
    } on ApiFailure catch (parentFailure) {
      profileFailure(parentFailure);
    }
  }

  Future<void> loadCondition(String id) {
    if (_conditionId != id) {
      _conditionId = id;
      conditionDetail.clear(keepCooldown: true);
    }
    return conditionDetail.load(
      () => repository.condition(profile.id, id),
      onFailure: (failure) => unawaited(_checkParent(failure)),
    );
  }

  Future<void> loadMedicine(String id) {
    if (_medicineId != id) {
      _medicineId = id;
      medicineDetail.clear(keepCooldown: true);
    }
    return medicineDetail.load(
      () => repository.medicine(profile.id, id),
      onFailure: (failure) => unawaited(_checkParent(failure)),
    );
  }

  void dispose() {
    _disposed = true;
    summary.dispose();
    medicines.dispose();
    conditions.dispose();
    conditionDetail.dispose();
    medicineDetail.dispose();
  }
}
