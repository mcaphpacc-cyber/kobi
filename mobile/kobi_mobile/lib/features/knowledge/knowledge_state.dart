import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../core/errors/api_failure.dart';
import '../../core/network/api_client.dart';
import '../health/health_state.dart';
import '../health/paged_state.dart';
import 'knowledge_models.dart';

class KnowledgeRepository {
  KnowledgeRepository(this.api);
  final ApiClient api;
  // Public reads deliberately never use SessionController.authorized or tokens.
  Future<ReadPage<DiseaseSummary>> catalog(int page) async {
    final value = await api.requestEnvelope(
      'GET',
      '/diseases',
      query: {'page': '$page', 'page_size': '20'},
    );
    return ReadPage.fromApi(value.data, value.meta, DiseaseSummary.fromApi);
  }

  Future<SearchResults<T>> _search<T>(
    String path,
    String query,
    T Function(Object?) parse,
  ) async {
    final value = await api.requestEnvelope('GET', path, query: {'q': query});
    return SearchResults.fromApi(value.data, value.meta, parse);
  }

  Future<SearchResults<DiseaseSummary>> diseases(String query) =>
      _search('/diseases/search', query, DiseaseSummary.fromApi);
  Future<SearchResults<RegistryName>> substances(String query) =>
      _search('/substances/search', query, RegistryName.fromApi);
  Future<DiseaseDetail> detail(String slug) async {
    if (slug.length > 255 ||
        !RegExp(r'^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$').hasMatch(slug)) {
      throw ApiFailure.malformed;
    }
    // Slugs are the returned canonical path segment; Uri encodes the request.
    final value = DiseaseDetail.fromApi(
      await api.request('GET', '/diseases/$slug'),
    );
    if (value.disease.slug != slug) throw ApiFailure.malformed;
    return value;
  }
}

class SearchState<T> extends ChangeNotifier {
  SearchState(this.fetch);
  final Future<SearchResults<T>> Function(String query) fetch;
  final result = LoadState<SearchResults<T>>();
  String rawQuery = '', query = '';
  Timer? _debounce;
  bool waiting = false;
  bool get valid => query.runes.length >= 2 && query.runes.length <= 100;
  void change(String value) {
    _debounce?.cancel();
    rawQuery = value;
    query = value
        .replaceAll(
          RegExp(
            r'[\s\u00a0\u1680\u2000-\u200a\u2028\u2029\u202f\u205f\u3000]+',
          ),
          ' ',
        )
        .trim();
    result.clear(
      keepCooldown: true,
    ); // Invalidates requests from every prior query.
    waiting = valid;
    if (valid) {
      _debounce = Timer(const Duration(milliseconds: 350), () {
        waiting = false;
        notifyListeners();
        unawaited(retry());
      });
    }
    notifyListeners();
  }

  Future<void> retry() async {
    if (!valid || waiting) return;
    final selected = query;
    await result.load(() => fetch(selected));
  }

  @override
  void dispose() {
    _debounce?.cancel();
    result.dispose();
    super.dispose();
  }
}

enum KnowledgePage { landing, catalog, detail, substances }

class KnowledgeController extends ChangeNotifier {
  KnowledgeController(this.repository) {
    catalog = PagedState(repository.catalog, (item) => item.id);
    diseases = SearchState(repository.diseases);
    substances = SearchState(repository.substances);
  }
  final KnowledgeRepository repository;
  late final PagedState<DiseaseSummary> catalog;
  late final SearchState<DiseaseSummary> diseases;
  late final SearchState<RegistryName> substances;
  final detail = LoadState<DiseaseDetail>();
  KnowledgePage page = KnowledgePage.landing;
  String? slug;
  void showCatalog() {
    page = KnowledgePage.catalog;
    notifyListeners();
    if (!catalog.initialized && !catalog.request.loading) {
      unawaited(catalog.load());
    }
  }

  void showSubstances() {
    page = KnowledgePage.substances;
    notifyListeners();
  }

  Future<void> open(String value) async {
    if (slug != value) detail.clear(keepCooldown: true);
    slug = value;
    page = KnowledgePage.detail;
    notifyListeners();
    await detail.load(() => repository.detail(value));
  }

  void back() {
    page = page == KnowledgePage.detail
        ? KnowledgePage.catalog
        : KnowledgePage.landing;
    notifyListeners();
  }

  @override
  void dispose() {
    catalog.dispose();
    diseases.dispose();
    substances.dispose();
    detail.dispose();
    super.dispose();
  }
}
