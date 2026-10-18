import 'package:flutter/foundation.dart';
import '../../core/errors/api_failure.dart';
import 'health_models.dart';
import 'health_state.dart';

class ReadPage<T> {
  ReadPage(this.items, this.page, this.total, this.hasMore);
  factory ReadPage.fromApi(
    Object data,
    Map<String, dynamic> meta,
    T Function(Object?) parse,
  ) {
    final page = meta['page'],
        size = meta['page_size'],
        total = meta['total'],
        more = meta['has_more'];
    if (page is! int ||
        page < 1 ||
        page > 10000 ||
        size is! int ||
        size != 20 ||
        total is! int ||
        total < 0 ||
        more is! bool ||
        more != (page * 20 < total)) {
      throw ApiFailure.malformed;
    }
    final items = collection(data, parse);
    if (items.length > 20 || (more && items.isEmpty)) {
      throw ApiFailure.malformed;
    }
    return ReadPage(items, page, total, more);
  }
  final List<T> items;
  final int page, total;
  final bool hasMore;
}

/// Explicit paging, with disposal/generation guards provided by LoadState.
class PagedState<T> extends ChangeNotifier {
  PagedState(this.fetch, this.identity, {this.onFailure, this.restartIf});
  final Future<ReadPage<T>> Function(int page) fetch;
  final String Function(T) identity;
  final void Function(ApiFailure)? onFailure;
  final bool Function(List<T> previous, List<T> incoming)? restartIf;
  final request = LoadState<ReadPage<T>>();
  List<T> items = [];
  int page = 0, total = 0, _generation = 0;
  bool initialized = false, hasMore = false, _disposed = false;
  Future<void> load({bool more = false}) async {
    if (_disposed ||
        request.loading ||
        !request.retryAllowed ||
        (more && (!hasMore || page >= 10000))) {
      return;
    }
    if (!more) {
      ++_generation;
      items = [];
      page = 0;
      total = 0;
      initialized = false;
      hasMore = false;
    }
    final generation = _generation, next = page + 1;
    final pending = request.load(() async {
      var result = await fetch(next);
      if (result.page != next) throw ApiFailure.malformed;
      if (more && restartIf?.call(items, result.items) == true) {
        // A server-observed day change invalidates the old occurrences even if
        // fetching the replacement first page fails.
        if (_disposed || generation != _generation) return result;
        items = [];
        page = 0;
        total = 0;
        initialized = false;
        hasMore = false;
        notifyListeners();
        result = await fetch(1);
        if (result.page != 1) throw ApiFailure.malformed;
      }
      return result;
    }, onFailure: onFailure);
    notifyListeners();
    await pending;
    if (_disposed || generation != _generation) return;
    if (request.value case final ReadPage<T> value) {
      if (value.page == 1) items = [];
      final seen = items.map(identity).toSet();
      items = [
        ...items,
        ...value.items.where((item) => seen.add(identity(item))),
      ];
      page = value.page;
      total = value.total;
      hasMore = value.hasMore;
      initialized = true;
    }
    notifyListeners();
  }

  /// Supersede an older GET when a mutation begins, retaining confirmed rows.
  void invalidatePendingLoad() {
    ++_generation;
    request.clear(keepCooldown: true);
  }

  void clear() {
    ++_generation;
    items = [];
    page = 0;
    total = 0;
    initialized = false;
    hasMore = false;
    request.clear();
    if (!_disposed) notifyListeners();
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
