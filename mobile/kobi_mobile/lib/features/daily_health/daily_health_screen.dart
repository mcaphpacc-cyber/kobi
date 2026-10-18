import 'package:flutter/material.dart';
import '../health/health_screens.dart';
import '../health/paged_view.dart';
import 'daily_health_state.dart';
import 'schedule_models.dart';

class DailyHealthScreen extends StatelessWidget {
  const DailyHealthScreen({
    super.key,
    required this.scope,
    required this.dueToday,
  });
  final DailyHealthScope scope;
  final bool dueToday;
  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      heading(context, dueToday ? 'Due Today' : 'Medication Schedule'),
      Text(
        dueToday
            ? 'The day’s recorded schedule, including later times. Dates and times belong to each slot’s timezone. Refresh for the latest schedule.'
            : 'Schedules recorded in KOBI. Active and inactive slots are shown; these are not medical recommendations.',
      ),
      const SizedBox(height: 16),
      if (dueToday)
        ListenableBuilder(
          listenable: scope.health.medicines,
          builder: (context, _) => PagedView<DueEntry>(
            state: scope.due,
            empty:
                'Nothing scheduled today. No schedule entries were returned for today; this does not mean there are no medicines.',
            item: (entry) => _dueCard(context, entry),
          ),
        )
      else
        PagedView<MedicationSlot>(
          state: scope.schedules,
          empty:
              'No medication schedules have been recorded for this health profile.',
          item: (entry) => _card(context, entry.localTime, entry.timezone, [
            entry.active ? 'Active' : 'Inactive',
            'Starts: ${entry.starts.display}',
            'Ends: ${entry.ends?.display ?? 'Not recorded'}',
          ]),
        ),
    ],
  );
  Widget _dueCard(BuildContext context, DueEntry entry) {
    final due = scope.due, name = scope.due.medicineName(entry);
    final pending = due.pending(entry);
    return Card(
      key: ValueKey('due-${entry.id}'),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              name ?? 'Scheduled medication',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            Text(
              entry.localTime,
              style: Theme.of(context).textTheme.titleLarge,
            ),
            Text(entry.timezone),
            Text('Scheduled on: ${entry.scheduledOn.display}'),
            Semantics(
              liveRegion: true,
              label: 'Current recorded state: ${entry.stateLabel}',
              excludeSemantics: true,
              child: Text(entry.stateLabel),
            ),
            if (due.canRecord && name == null) ...[
              const Text(
                'Medication details unavailable. Load the recorded name before recording an action.',
              ),
              OutlinedButton(
                onPressed: scope.health.medicines.loading
                    ? null
                    : scope.health.loadMedicines,
                child: const Text('Refresh medication details'),
              ),
            ],
            if (due.canRecord && name != null)
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final state in ['taken', 'skipped'])
                    if (entry.state != state)
                      Semantics(
                        label:
                            '${entry.state == null ? 'Record' : 'Change to'} ${state == 'taken' ? 'Taken' : 'Skipped'} for $name',
                        child: FilledButton.tonal(
                          key: ValueKey('${entry.id}-$state'),
                          onPressed: due.canAct(entry)
                              ? () => due.record(entry, state)
                              : null,
                          child: Text(
                            '${entry.state == null ? 'Record' : 'Change to'} ${state == 'taken' ? 'Taken' : 'Skipped'}',
                          ),
                        ),
                      ),
                ],
              ),
            if (pending)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Recording medication action…',
                        semanticsLabel:
                            'Recording medication action in progress',
                      ),
                    ),
                  ],
                ),
              ),
            if (due.actionError(entry) case final String error)
              Semantics(liveRegion: true, child: Text(error)),
            if (due.notices[entry.id] case final String notice)
              Semantics(liveRegion: true, child: Text(notice)),
          ],
        ),
      ),
    );
  }

  Widget _card(
    BuildContext context,
    String time,
    String timezone,
    List<String> lines,
  ) => Card(
    child: Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Scheduled medication'),
          Text(time, style: Theme.of(context).textTheme.titleLarge),
          Text(timezone),
          for (final line in lines)
            Padding(padding: const EdgeInsets.only(top: 8), child: Text(line)),
        ],
      ),
    ),
  );
}
