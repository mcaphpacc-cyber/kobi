import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/errors/api_failure.dart';
import '../health/health_screens.dart';
import '../health/paged_view.dart';
import 'knowledge_models.dart';
import 'knowledge_state.dart';

class KnowledgeArea extends StatefulWidget {
  const KnowledgeArea({super.key, required this.controller});
  final KnowledgeController controller;
  @override
  State<KnowledgeArea> createState() => _KnowledgeAreaState();
}

class _KnowledgeAreaState extends State<KnowledgeArea> {
  late final TextEditingController _diseaseInput, _substanceInput;
  KnowledgeController get controller => widget.controller;
  @override
  void initState() {
    super.initState();
    _diseaseInput = TextEditingController(text: controller.diseases.rawQuery);
    _substanceInput = TextEditingController(
      text: controller.substances.rawQuery,
    );
  }

  @override
  void dispose() {
    _diseaseInput.dispose();
    _substanceInput.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: controller,
    builder: (context, _) => PopScope(
      canPop: controller.page == KnowledgePage.landing,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop) controller.back();
      },
      child: HealthLayout(
        children: [
          if (controller.page != KnowledgePage.landing)
            Align(
              alignment: Alignment.centerLeft,
              child: BackButton(onPressed: controller.back),
            ),
          ..._content(context),
        ],
      ),
    ),
  );
  List<Widget> _content(BuildContext context) {
    switch (controller.page) {
      case KnowledgePage.landing:
        return [
          heading(context, 'Public Knowledge'),
          const Text('Browse KOBI’s public information without signing in.'),
          Card(
            child: ListTile(
              title: const Text('Diseases'),
              subtitle: const Text(
                'Explore and search KOBI’s disease knowledge.',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: controller.showCatalog,
            ),
          ),
          Card(
            child: ListTile(
              title: const Text('Medicine / Substance Registry'),
              subtitle: const Text(
                'Search generic substances and registry names.',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: controller.showSubstances,
            ),
          ),
        ];
      case KnowledgePage.catalog:
        return [
          heading(context, 'Disease Catalog'),
          _searchBox('Search diseases', _diseaseInput, controller.diseases),
          ListenableBuilder(
            listenable: controller.diseases,
            builder: (context, _) => controller.diseases.rawQuery.isNotEmpty
                ? _searchResults<DiseaseSummary>(
                    controller.diseases,
                    'No matching diseases found.',
                    _disease,
                  )
                : PagedView<DiseaseSummary>(
                    state: controller.catalog,
                    empty: 'No public diseases are available.',
                    item: _disease,
                  ),
          ),
        ];
      case KnowledgePage.substances:
        return [
          heading(context, 'Medicine / Substance Registry'),
          const Text(
            'Legacy registry/substance information. This is a generic-name lookup, not curated Medicine Knowledge or prescribing advice.',
          ),
          _searchBox(
            'Search substance / generic name',
            _substanceInput,
            controller.substances,
          ),
          _searchResults<RegistryName>(
            controller.substances,
            'No matching substances found in KOBI’s current registry.',
            (item) => Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Text(item.name),
              ),
            ),
          ),
        ];
      case KnowledgePage.detail:
        return [
          ListenableBuilder(
            listenable: controller.detail,
            builder: (context, _) {
              final state = controller.detail;
              if (state.loading) {
                return const Center(
                  child: CircularProgressIndicator(
                    semanticsLabel: 'Loading disease information',
                  ),
                );
              }
              if (state.error case final ApiFailure failure) {
                if (failure.status == 404) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      heading(context, 'Disease not found'),
                      const Text('This public disease page is unavailable.'),
                      OutlinedButton(
                        onPressed: controller.back,
                        child: const Text('Back to diseases'),
                      ),
                    ],
                  );
                }
                return _failure(
                  failure,
                  () => controller.open(controller.slug!),
                  state.retryAllowed,
                );
              }
              if (state.value case final DiseaseDetail detail) {
                return _detail(context, detail);
              }
              return _failure(
                ApiFailure.malformed,
                () => controller.open(controller.slug!),
                state.retryAllowed,
              );
            },
          ),
        ];
    }
  }

  Widget _searchBox<T>(
    String title,
    TextEditingController input,
    SearchState<T> state,
  ) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 16),
    child: TextField(
      controller: input,
      onChanged: state.change,
      decoration: InputDecoration(
        labelText: title,
        helperText: 'Enter 2–100 characters',
        suffixIcon: IconButton(
          tooltip: 'Clear search',
          icon: const Icon(Icons.clear),
          onPressed: () {
            input.clear();
            state.change('');
          },
        ),
      ),
      textInputAction: TextInputAction.search,
    ),
  );
  Widget _searchResults<T>(
    SearchState<T> state,
    String empty,
    Widget Function(T) item,
  ) => ListenableBuilder(
    listenable: Listenable.merge([state, state.result]),
    builder: (context, _) {
      if (!state.valid) return const Text('Enter 2–100 characters to search.');
      if (state.waiting || state.result.loading) {
        return const Center(
          child: CircularProgressIndicator(
            semanticsLabel: 'Searching public knowledge',
          ),
        );
      }
      if (state.result.error case final ApiFailure failure) {
        return _failure(failure, state.retry, state.result.retryAllowed);
      }
      if (state.result.value case final SearchResults<T> value) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (value.items.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Text(empty),
              ),
            for (final result in value.items) item(result),
            if (value.hasMore)
              const Text('More matches may be available. Refine your search.'),
          ],
        );
      }
      return Column(
        children: [
          if (!state.result.retryAllowed)
            const Text('Please wait before retrying.'),
          OutlinedButton(
            onPressed: state.result.retryAllowed ? state.retry : null,
            child: const Text('Search'),
          ),
        ],
      );
    },
  );
  Widget _failure(ApiFailure failure, VoidCallback retry, bool allowed) =>
      failure.status == 401 || failure.status == 403
      ? Column(
          children: [
            const Text(
              'Public information is unavailable. Please try again later.',
            ),
            OutlinedButton(
              onPressed: allowed ? retry : null,
              child: const Text('Retry'),
            ),
          ],
        )
      : HealthFailure(failure: failure, retry: retry, allowed: allowed);
  Widget _disease(DiseaseSummary item) => Card(
    child: ListTile(
      title: Text(item.name),
      subtitle: Text(
        [
          if (item.hindiName != null) item.hindiName!,
          if (item.bodySystem != null) item.bodySystem!,
          if (item.gender != null) 'Gender: ${item.gender}',
        ].join('\n'),
      ),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => controller.open(item.slug),
    ),
  );
  Widget _detail(BuildContext context, DiseaseDetail detail) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      heading(context, detail.disease.name),
      if (detail.disease.hindiName != null) Text(detail.disease.hindiName!),
      if (detail.disease.bodySystem != null) Text(detail.disease.bodySystem!),
      if (detail.disease.gender != null)
        Text('Gender: ${detail.disease.gender}'),
      const Text('Content language: English'),
      if (detail.urgency != null)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Text(detail.urgency!),
        ),
      for (final section in DiseaseDetail.sectionLabels.keys) ...[
        if (detail.sections[section] != null ||
            (section == 'causes' && detail.causes.isNotEmpty) ||
            detail.references[section]!.isNotEmpty) ...[
          heading(context, DiseaseDetail.sectionLabels[section]!),
          if (detail.sections[section] case final String body)
            _paragraphs(body),
          if (section == 'causes')
            for (final cause in detail.causes)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Text('• $cause'),
              ),
          if (detail.references[section]!.isNotEmpty) ...[
            const SizedBox(height: 16),
            const Text(
              'Sources',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            for (final source in detail.references[section]!)
              SourceCard(source: source),
          ],
        ],
      ],
      if (detail.sections.values.every((s) => s == null) &&
          detail.causes.isEmpty)
        const Padding(
          padding: EdgeInsets.symmetric(vertical: 16),
          child: Text('Section text is not available for this disease.'),
        ),
    ],
  );
  Widget _paragraphs(String value) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      for (final paragraph in value.split(RegExp(r'\r?\n\r?\n')))
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Text(paragraph),
        ),
    ],
  );
}

class SourceCard extends StatefulWidget {
  const SourceCard({super.key, required this.source});
  final DiseaseSource source;
  @override
  State<SourceCard> createState() => _SourceCardState();
}

class _SourceCardState extends State<SourceCard> {
  String? _error;
  bool _opening = false;
  Future<void> _open() async {
    final uri = widget.source.externalUrl;
    if (uri == null || _opening) return;
    setState(() {
      _opening = true;
      _error = null;
    });
    var opened = false;
    try {
      opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      opened = false;
    }
    if (mounted) {
      setState(() {
        _opening = false;
        if (!opened) {
          _error = 'Unable to open this source. You can copy its URL below.';
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            widget.source.title,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          Text(widget.source.organization),
          if (widget.source.date != null)
            Text(
              '${widget.source.dateType ?? 'Source date'}: ${widget.source.date}',
            ),
          SelectableText(widget.source.url),
          if (widget.source.externalUrl != null)
            OutlinedButton(
              onPressed: _opening ? null : _open,
              child: const Text('Open source in browser'),
            ),
          if (_error != null) Semantics(liveRegion: true, child: Text(_error!)),
        ],
      ),
    ),
  );
}
