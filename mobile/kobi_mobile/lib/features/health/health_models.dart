import '../../core/errors/api_failure.dart';

Map<String, dynamic> object(Object? value) {
  if (value is! Map<String, dynamic>) throw ApiFailure.malformed;
  return value;
}

String text(Map<String, dynamic> data, String key) {
  final value = data[key];
  if (value is! String) throw ApiFailure.malformed;
  return value;
}

String? optionalText(Map<String, dynamic> data, String key) {
  if (!data.containsKey(key)) throw ApiFailure.malformed;
  return data[key] == null ? null : text(data, key);
}

String decimalId(Map<String, dynamic> data, String key) {
  final value = text(data, key);
  if (!RegExp(r'^[1-9][0-9]*$').hasMatch(value)) throw ApiFailure.malformed;
  return value;
}

String choice(Map<String, dynamic> data, String key, Set<String> values) {
  final value = text(data, key);
  if (!values.contains(value)) throw ApiFailure.malformed;
  return value;
}

/// A calendar value, never converted into the device's timezone.
class CalendarDate {
  const CalendarDate._(this.year, this.month, this.day);
  final int year, month, day;
  static CalendarDate? fromApi(Object? value) {
    if (value == null) return null;
    if (value is! String || !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(value)) {
      throw ApiFailure.malformed;
    }
    final parts = value.split('-').map(int.parse).toList();
    final date = DateTime.utc(parts[0], parts[1], parts[2]);
    if (parts[0] < 1 ||
        date.year != parts[0] ||
        date.month != parts[1] ||
        date.day != parts[2]) {
      throw ApiFailure.malformed;
    }
    return CalendarDate._(parts[0], parts[1], parts[2]);
  }

  String get display {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return '$day ${months[month - 1]} $year';
  }
}

CalendarDate? date(Map<String, dynamic> data, String key) {
  if (!data.containsKey(key)) throw ApiFailure.malformed;
  return CalendarDate.fromApi(data[key]);
}

class HealthProfile {
  const HealthProfile(this.id, this.name, this.type);
  factory HealthProfile.fromApi(Object? value) {
    final data = object(value);
    return HealthProfile(
      decimalId(data, 'id'),
      text(data, 'full_name'),
      choice(data, 'profile_type', {'self', 'family', 'dependent'}),
    );
  }
  final String id, name, type;
}

class MedicalSummary {
  MedicalSummary(this.profile, this.counts);
  factory MedicalSummary.fromApi(Object? value) {
    final data = object(value);
    final counts = object(data['counts']);
    final parsed = <String, int>{};
    for (final key in labels.keys) {
      final count = counts[key];
      if (count is! int || count < 0) throw ApiFailure.malformed;
      parsed[key] = count;
    }
    return MedicalSummary(
      HealthProfile.fromApi(data['profile']),
      Map.unmodifiable(parsed),
    );
  }
  static const labels = {
    'conditions': 'Active / chronic conditions',
    'historical_conditions': 'Historical / resolved conditions',
    'current_medications': 'Current medicines',
    'allergies': 'Allergies',
    'procedures': 'Procedures',
  };
  final HealthProfile profile;
  final Map<String, int> counts;
}

class MedicalCondition {
  MedicalCondition(
    this.id,
    this.name,
    this.status,
    this.diagnosed,
    this.resolved,
  );
  factory MedicalCondition.fromApi(Object? value) {
    final data = object(value);
    return MedicalCondition(
      decimalId(data, 'id'),
      text(data, 'condition_name'),
      choice(data, 'status', {'active', 'chronic', 'historical', 'resolved'}),
      date(data, 'diagnosed_on'),
      date(data, 'resolved_on'),
    );
  }
  final String id, name, status;
  final CalendarDate? diagnosed, resolved;
}

class PersonalMedicine {
  PersonalMedicine(
    this.id,
    this.brand,
    this.generic,
    this.strength,
    this.dose,
    this.frequency,
    this.route,
    this.status,
    this.started,
    this.stopped,
  );
  factory PersonalMedicine.fromApi(Object? value) {
    final data = object(value);
    return PersonalMedicine(
      decimalId(data, 'id'),
      text(data, 'brand_name'),
      optionalText(data, 'generic_name'),
      optionalText(data, 'strength'),
      optionalText(data, 'dose'),
      optionalText(data, 'frequency'),
      optionalText(data, 'route'),
      choice(data, 'status', {'current', 'stopped', 'historical'}),
      date(data, 'started_on'),
      date(data, 'stopped_on'),
    );
  }
  final String id, brand, status;
  final String? generic, strength, dose, frequency, route;
  final CalendarDate? started, stopped;
}

List<T> collection<T>(Object? value, T Function(Object?) parse) {
  if (value is! List) throw ApiFailure.malformed;
  return List.unmodifiable(value.map(parse));
}

class ConditionPage {
  ConditionPage(this.items, this.page, this.pageSize, this.total, this.hasMore);
  factory ConditionPage.fromApi(Object? data, Map<String, dynamic> meta) {
    final page = meta['page'],
        size = meta['page_size'],
        total = meta['total'],
        more = meta['has_more'];
    if (page is! int ||
        page < 1 ||
        page > 10000 ||
        size is! int ||
        size < 1 ||
        size > 50 ||
        total is! int ||
        total < 0 ||
        more is! bool ||
        more != (page * size < total)) {
      throw ApiFailure.malformed;
    }
    final items = collection(data, MedicalCondition.fromApi);
    if (items.length > size || (more && items.isEmpty)) {
      throw ApiFailure.malformed;
    }
    return ConditionPage(items, page, size, total, more);
  }
  final List<MedicalCondition> items;
  final int page, pageSize, total;
  final bool hasMore;
}
