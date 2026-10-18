import 'package:flutter/material.dart';
import '../../core/errors/api_failure.dart';
import 'health_screens.dart';
import 'paged_state.dart';

class PagedView<T> extends StatelessWidget {
  const PagedView({
    super.key,
    required this.state,
    required this.empty,
    required this.item,
  });
  final PagedState<T> state;
  final String empty;
  final Widget Function(T) item;
  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: Listenable.merge([state, state.request]),
    builder: (context, _) => Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (state.initialized)
          Text('${state.items.length} loaded · ${state.total} recorded'),
        if (state.initialized && state.items.isEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 24),
            child: Text(empty),
          ),
        for (final value in state.items) item(value),
        if (state.request.loading)
          const Padding(
            padding: EdgeInsets.all(24),
            child: Center(
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading records',
              ),
            ),
          ),
        if (state.request.error case final ApiFailure failure)
          HealthFailure(
            failure: failure,
            retry: () => state.load(more: state.page > 0),
            allowed: state.request.retryAllowed,
          ),
        if (!state.request.loading &&
            state.request.error == null &&
            state.hasMore &&
            state.page < 10000)
          FilledButton(
            onPressed: () => state.load(more: true),
            child: const Text('Load more'),
          ),
        if (state.hasMore && state.page == 10000)
          const Text(
            'The API page limit has been reached. Refresh to start again.',
          ),
        OutlinedButton(
          onPressed: state.request.loading || !state.request.retryAllowed
              ? null
              : () => state.load(),
          child: const Text('Refresh'),
        ),
      ],
    ),
  );
}
