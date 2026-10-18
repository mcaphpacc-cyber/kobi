import '../../core/errors/api_failure.dart';
import '../health/health_models.dart';

class MedicationSlot {
  MedicationSlot(
    this.id,
    this.medicineId,
    this.localTime,
    this.timezone,
    this.starts,
    this.ends,
    this.active,
  );
  factory MedicationSlot.fromApi(Object? value) {
    final data = object(value);
    if (data['is_active'] is! bool) throw ApiFailure.malformed;
    return MedicationSlot(
      decimalId(data, 'id'),
      decimalId(data, 'health_medicine_id'),
      wallTime(data),
      zone(data),
      date(data, 'starts_on') ?? (throw ApiFailure.malformed),
      date(data, 'ends_on'),
      data['is_active'] as bool,
    );
  }
  final String id, medicineId, localTime, timezone;
  final CalendarDate starts;
  final CalendarDate? ends;
  final bool active;
}

class DueEntry {
  DueEntry(
    this.id,
    this.medicineId,
    this.localTime,
    this.timezone,
    this.scheduledOn,
    this.state,
  );
  factory DueEntry.fromApi(Object? value) {
    final data = object(value), state = optionalText(object(value), 'state');
    if (state != null && state != 'taken' && state != 'skipped') {
      throw ApiFailure.malformed;
    }
    return DueEntry(
      decimalId(data, 'id'),
      decimalId(data, 'health_medicine_id'),
      wallTime(data),
      zone(data),
      date(data, 'scheduled_on') ?? (throw ApiFailure.malformed),
      state,
    );
  }
  final String id, medicineId, localTime, timezone;
  final CalendarDate scheduledOn;
  final String? state;
  String get stateLabel => state == 'taken'
      ? 'Taken'
      : state == 'skipped'
      ? 'Skipped'
      : 'No checkoff recorded';
}

String wallTime(Map<String, dynamic> data) {
  final value = text(data, 'local_time');
  if (!RegExp(r'^([01][0-9]|2[0-3]):[0-5][0-9]$').hasMatch(value)) {
    throw ApiFailure.malformed;
  }
  return value;
}

String zone(Map<String, dynamic> data) {
  final value = text(data, 'timezone');
  // Display the server's zone label; never reinterpret it with the phone clock.
  if (value.isEmpty ||
      value.length > 100 ||
      RegExp(r'[\x00-\x1f\x7f]').hasMatch(value)) {
    throw ApiFailure.malformed;
  }
  return value;
}
