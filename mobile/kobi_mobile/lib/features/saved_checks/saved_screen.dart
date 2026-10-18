import 'package:flutter/material.dart';
import '../health/health_screens.dart';
import '../health/paged_view.dart';
import 'saved_models.dart';
import 'saved_state.dart';

class SavedChecksScreen extends StatelessWidget {
  const SavedChecksScreen({
    super.key,
    required this.controller,
    required this.open,
    this.detailId,
  });
  final SavedChecksController controller;
  final void Function(String) open;
  final String? detailId;
  @override
  Widget build(BuildContext context) => HealthLayout(
    children: [
      heading(
        context,
        detailId == null ? 'Saved Symptom Checks' : 'Saved Symptom Check',
      ),
      const Text(
        'Historical checks saved to your account, independent of the selected health profile.',
      ),
      const Text('Check and save times are shown in your device timezone.'),
      const SizedBox(height: 16),
      if (detailId == null)
        PagedView<SavedCheckMetadata>(
          state: controller.list,
          empty: 'No saved symptom checks yet.',
          item: (check) => Card(
            child: ListTile(
              title: Text('Symptom Check · ${check.checked.display}'),
              subtitle: Text(
                'Saved: ${check.saved.display}\n${check.selectedCount} symptoms selected\n${check.resultCount} results shown',
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => open(check.id),
            ),
          ),
        )
      else
        Loaded<SavedCheckDetail>(
          state: controller.detail,
          retry: () => controller.open(detailId!),
          content: (check) => Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              heading(context, 'Historical snapshot'),
              Text('Checked: ${check.checked.display}'),
              Text('Saved: ${check.metadata.saved.display}'),
              Text(
                '${check.metadata.selectedCount} symptoms selected · ${check.metadata.resultCount} results shown',
              ),
              Text('Snapshot version: ${check.version}'),
              heading(context, 'Selected symptoms'),
              for (final symptom in check.selected)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Text(symptom.label),
                ),
              heading(context, 'Saved summary'),
              Text('Total matches: ${check.totalMatches}'),
              Text('Displayed matches: ${check.displayedMatches}'),
              heading(context, 'Saved results'),
              if (check.results.isEmpty)
                const Text('No results were displayed in this saved check.'),
              for (final result in check.results) _result(context, result),
              heading(context, 'Disclaimer'),
              Text(check.disclaimer),
            ],
          ),
        ),
    ],
  );
  Widget _result(BuildContext context, SavedResult result) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Semantics(
            header: true,
            child: Text(
              result.label,
              style: Theme.of(context).textTheme.titleLarge,
            ),
          ),
          Text('${result.displayPercent}% Symptom Match'),
          Text(result.matchLevel),
          Text('User match score: ${result.userScore}'),
          Text('Coverage: ${result.coverage}%'),
          const SizedBox(height: 12),
          const Text(
            'Saved reasons',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          for (final reason in result.reason)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Text(reason),
            ),
          const SizedBox(height: 12),
          const Text(
            'Saved explanation',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          for (final line in result.explanation)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Text(line),
            ),
        ],
      ),
    ),
  );
}
