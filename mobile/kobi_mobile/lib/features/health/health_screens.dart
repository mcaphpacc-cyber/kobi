import 'package:flutter/material.dart';
import '../../core/errors/api_failure.dart';
import 'health_models.dart';
import 'health_state.dart';

enum HealthPage {
  profile,
  summary,
  conditions,
  condition,
  medicines,
  medicine,
  schedules,
  dueToday,
  savedChecks,
}

class HealthRoute {
  const HealthRoute(this.page, [this.id]);
  final HealthPage page;
  final String? id;
}

String label(String value) => value.isEmpty
    ? 'Not recorded'
    : '${value[0].toUpperCase()}${value.substring(1)}';

class HealthLayout extends StatelessWidget {
  const HealthLayout({super.key, required this.children});
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => SingleChildScrollView(
    padding: const EdgeInsets.all(16),
    child: Center(
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 720),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: children,
        ),
      ),
    ),
  );
}

Widget heading(BuildContext context, String value) => Padding(
  padding: const EdgeInsets.symmetric(vertical: 16),
  child: Semantics(
    header: true,
    child: Text(value, style: Theme.of(context).textTheme.headlineSmall),
  ),
);

class HealthFailure extends StatelessWidget {
  const HealthFailure({
    super.key,
    required this.failure,
    required this.retry,
    required this.allowed,
  });
  final ApiFailure failure;
  final VoidCallback retry;
  final bool allowed;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Semantics(
        liveRegion: true,
        child: Text(
          failure.status == 403 || failure.status == 404
              ? 'This record is unavailable. Go back to the list or retry.'
              : failure.message,
        ),
      ),
      const SizedBox(height: 12),
      if (!allowed) const Text('Please wait before retrying.'),
      OutlinedButton(
        onPressed: allowed ? retry : null,
        child: const Text('Retry'),
      ),
    ],
  );
}

class Loaded<T> extends StatelessWidget {
  const Loaded({
    super.key,
    required this.state,
    required this.retry,
    required this.content,
  });
  final LoadState<T> state;
  final VoidCallback retry;
  final Widget Function(T) content;
  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: state,
    builder: (context, _) {
      if (state.loading) {
        return const Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: CircularProgressIndicator(
              semanticsLabel: 'Loading health information',
            ),
          ),
        );
      }
      if (state.error case final ApiFailure failure) {
        return HealthFailure(
          failure: failure,
          retry: retry,
          allowed: state.retryAllowed,
        );
      }
      if (state.value case final T value) return content(value);
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (!state.retryAllowed) const Text('Please wait before retrying.'),
          OutlinedButton(
            onPressed: state.retryAllowed ? retry : null,
            child: const Text('Load information'),
          ),
        ],
      );
    },
  );
}

class ProfileSelector extends StatelessWidget {
  const ProfileSelector({super.key, required this.controller});
  final ProfileController controller;
  @override
  Widget build(BuildContext context) => ListenableBuilder(
    listenable: Listenable.merge([
      controller,
      controller.list,
      controller.selection,
    ]),
    builder: (context, _) => HealthLayout(
      children: [
        heading(context, 'Choose a health profile'),
        const Text(
          'Choose whose health information to view. Only profiles available to your account appear here.',
        ),
        if (controller.notice case final String notice)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Text(notice),
          ),
        if (controller.selection.loading)
          const Padding(
            padding: EdgeInsets.all(24),
            child: CircularProgressIndicator(
              semanticsLabel: 'Checking profile access',
            ),
          ),
        if (controller.selection.error case final ApiFailure failure)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 16),
            child: Text(
              failure.status == 404 || failure.status == 403
                  ? 'Profile unavailable.'
                  : failure.message,
            ),
          ),
        Loaded<List<HealthProfile>>(
          state: controller.list,
          retry: () => controller.refresh(),
          content: (profiles) => Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (profiles.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 24),
                  child: Text(
                    'No accessible health profiles. This app can view existing profiles; profile creation is not available here.',
                  ),
                ),
              for (final profile in profiles)
                Card(
                  child: ListTile(
                    title: Text(profile.name),
                    subtitle: Text(label(profile.type)),
                    trailing: const Icon(Icons.chevron_right),
                    enabled:
                        !controller.selection.loading &&
                        controller.selection.retryAllowed,
                    onTap: () => controller.select(profile),
                  ),
                ),
              if (!controller.selection.retryAllowed)
                const Text('Please wait before selecting a profile again.'),
              OutlinedButton(
                onPressed:
                    controller.list.loading ||
                        controller.selection.loading ||
                        !controller.list.retryAllowed
                    ? null
                    : () => controller.refresh(),
                child: const Text('Refresh profiles'),
              ),
            ],
          ),
        ),
      ],
    ),
  );
}

class HealthContent extends StatelessWidget {
  const HealthContent({
    super.key,
    required this.scope,
    required this.route,
    required this.open,
    required this.changeProfile,
    this.dailyContent,
  });
  final HealthScope scope;
  final HealthRoute? route;
  final void Function(HealthRoute) open;
  final VoidCallback changeProfile;
  final Widget? dailyContent;
  Widget _link(String title, HealthPage page) => Card(
    child: ListTile(
      title: Text(title),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => open(HealthRoute(page)),
    ),
  );
  @override
  Widget build(BuildContext context) => HealthLayout(
    children: [
      Card(
        color: Theme.of(context).colorScheme.primaryContainer,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Selected health profile'),
              Text(
                scope.profile.name,
                style: Theme.of(context).textTheme.titleLarge,
              ),
              Text(label(scope.profile.type)),
              TextButton(
                onPressed: changeProfile,
                child: const Text('Change profile'),
              ),
            ],
          ),
        ),
      ),
      ..._body(context),
    ],
  );
  List<Widget> _body(BuildContext context) {
    switch (route?.page) {
      case null:
        return [
          heading(context, 'Health Dashboard'),
          const Text(
            'An overview of recorded health information for the selected profile.',
          ),
          _link('Due Today', HealthPage.dueToday),
          _link('Medication Schedule', HealthPage.schedules),
          _link('Saved Symptom Checks', HealthPage.savedChecks),
          _summary(),
          _link('Medical Summary', HealthPage.summary),
          _link('Conditions', HealthPage.conditions),
          _link('Personal Medicines', HealthPage.medicines),
          _link('Health Profile', HealthPage.profile),
        ];
      case HealthPage.profile:
        return [
          heading(context, 'Health Profile'),
          _field('Name', scope.profile.name),
          _field('Profile type', label(scope.profile.type)),
          const Text(
            'The mobile API provides name and profile type. Other profile fields are not available here.',
          ),
          _link('Medical Summary', HealthPage.summary),
          _link('Conditions', HealthPage.conditions),
          _link('Personal Medicines', HealthPage.medicines),
          _link('Due Today', HealthPage.dueToday),
          _link('Medication Schedule', HealthPage.schedules),
          _link('Saved Symptom Checks', HealthPage.savedChecks),
        ];
      case HealthPage.schedules:
      case HealthPage.dueToday:
        return [?dailyContent];
      case HealthPage.savedChecks:
        return const [];
      case HealthPage.summary:
        return [
          heading(context, 'Medical Summary'),
          const Text(
            'Recorded counts only. This is not a complete clinical report.',
          ),
          _summary(),
          _link('View Conditions', HealthPage.conditions),
          _link('View Personal Medicines', HealthPage.medicines),
        ];
      case HealthPage.conditions:
        return [heading(context, 'Conditions'), _conditions()];
      case HealthPage.medicines:
        return [
          heading(context, 'Personal Medicines'),
          const Text('Your recorded medicines, not prescribing advice.'),
          Loaded<List<PersonalMedicine>>(
            state: scope.medicines,
            retry: scope.loadMedicines,
            content: (items) => Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (items.isEmpty)
                  const Text('No medicines recorded for this profile.'),
                for (final item in items)
                  Card(
                    child: ListTile(
                      title: Text(item.brand),
                      subtitle: Text(
                        [
                          if (item.generic != null && item.generic!.isNotEmpty)
                            item.generic!,
                          if (item.strength != null &&
                              item.strength!.isNotEmpty)
                            item.strength!,
                          [item.dose, item.frequency, item.route]
                              .whereType<String>()
                              .where((s) => s.isNotEmpty)
                              .join(' · '),
                          label(item.status),
                          if (item.started != null)
                            'Started: ${item.started!.display}',
                          if (item.stopped != null)
                            'Stopped: ${item.stopped!.display}',
                        ].where((s) => s.isNotEmpty).join('\n'),
                      ),
                      trailing: const Icon(Icons.chevron_right),
                      onTap: () =>
                          open(HealthRoute(HealthPage.medicine, item.id)),
                    ),
                  ),
                OutlinedButton(
                  onPressed: scope.loadMedicines,
                  child: const Text('Refresh medicines'),
                ),
              ],
            ),
          ),
        ];
      case HealthPage.condition:
        return [
          heading(context, 'Condition Detail'),
          Loaded<MedicalCondition>(
            state: scope.conditionDetail,
            retry: () => scope.loadCondition(route!.id!),
            content: (item) => Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _field('Condition', item.name),
                _field('Status', label(item.status)),
                _field('Diagnosed on', item.diagnosed?.display),
                _field('Resolved on', item.resolved?.display),
              ],
            ),
          ),
        ];
      case HealthPage.medicine:
        return [
          heading(context, 'Medicine Detail'),
          Loaded<PersonalMedicine>(
            state: scope.medicineDetail,
            retry: () => scope.loadMedicine(route!.id!),
            content: (item) => Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _field('Brand name', item.brand),
                _field('Generic name', item.generic),
                _field('Strength', item.strength),
                _field('Dose', item.dose),
                _field('Frequency', item.frequency),
                _field('Route', item.route),
                _field('Status', label(item.status)),
                _field('Started on', item.started?.display),
                _field('Stopped on', item.stopped?.display),
                const Text(
                  'Recorded personal medication information only. No clinical recommendations are provided.',
                ),
              ],
            ),
          ),
        ];
    }
  }

  Widget _summary() => Padding(
    padding: const EdgeInsets.symmetric(vertical: 16),
    child: Loaded<MedicalSummary>(
      state: scope.summary,
      retry: scope.loadSummary,
      content: (summary) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (final key in MedicalSummary.labels.keys)
            Card(
              child: ListTile(
                title: Text(
                  '${MedicalSummary.labels[key]}: ${summary.counts[key]}',
                ),
                trailing:
                    {
                      'conditions',
                      'historical_conditions',
                      'current_medications',
                    }.contains(key)
                    ? const Icon(Icons.chevron_right)
                    : null,
                onTap: key == 'current_medications'
                    ? () => open(const HealthRoute(HealthPage.medicines))
                    : key == 'conditions' || key == 'historical_conditions'
                    ? () => open(const HealthRoute(HealthPage.conditions))
                    : null,
              ),
            ),
          const Text(
            'Allergy and procedure details are not available in this version.',
          ),
          OutlinedButton(
            onPressed: scope.loadSummary,
            child: const Text('Refresh summary'),
          ),
        ],
      ),
    ),
  );
  Widget _conditions() {
    final state = scope.conditions;
    return ListenableBuilder(
      listenable: Listenable.merge([state, state.request]),
      builder: (context, _) => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (state.initialized)
            Text('${state.items.length} loaded · ${state.total} recorded'),
          if (state.initialized && state.items.isEmpty)
            const Text('No conditions recorded for this profile.'),
          for (final item in state.items)
            Card(
              child: ListTile(
                title: Text(item.name),
                subtitle: Text(
                  [
                    label(item.status),
                    if (item.diagnosed != null)
                      'Diagnosed: ${item.diagnosed!.display}',
                    if (item.resolved != null)
                      'Resolved: ${item.resolved!.display}',
                  ].join('\n'),
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => open(HealthRoute(HealthPage.condition, item.id)),
              ),
            ),
          if (state.loading)
            const Padding(
              padding: EdgeInsets.all(24),
              child: CircularProgressIndicator(
                semanticsLabel: 'Loading conditions',
              ),
            ),
          if (state.error case final ApiFailure failure)
            HealthFailure(
              failure: failure,
              retry: () => state.load(more: state.page > 0),
              allowed: state.request.retryAllowed,
            ),
          if (!state.loading && state.error == null && state.hasMore)
            FilledButton(
              onPressed: () => state.load(more: true),
              child: const Text('Load more'),
            ),
          OutlinedButton(
            onPressed: state.loading || !state.request.retryAllowed
                ? null
                : () => state.load(),
            child: const Text('Refresh conditions'),
          ),
        ],
      ),
    );
  }

  Widget _field(String title, String? value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: 10),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        Text(value == null || value.isEmpty ? 'Not recorded' : value),
      ],
    ),
  );
}
