import '../../core/errors/api_failure.dart';
import '../health/health_models.dart';

class SavedInstant {
  SavedInstant._(this.raw, this.utc);
  factory SavedInstant.fromApi(Object? value) {
    if (value is! String ||
        !RegExp(
          r'^\d{4}-\d{2}-\d{2}T([01]\d|2[0-3]):[0-5]\d:[0-5]\dZ$',
        ).hasMatch(value)) {
      throw ApiFailure.malformed;
    }
    CalendarDate.fromApi(value.substring(0, 10));
    final instant = DateTime.tryParse(value);
    if (instant == null || !instant.isUtc) throw ApiFailure.malformed;
    return SavedInstant._(value, instant);
  }
  final String raw;
  final DateTime utc;
  String get display {
    final local = utc.toLocal();
    final day = CalendarDate.fromApi(
      '${local.year.toString().padLeft(4, '0')}-${local.month.toString().padLeft(2, '0')}-${local.day.toString().padLeft(2, '0')}',
    )!;
    return '${day.display}, ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }
}

int count(
  Map<String, dynamic> data,
  String key, {
  int max = 2147483647,
  int min = 0,
}) {
  final value = data[key];
  if (value is! int || value < min || value > max) throw ApiFailure.malformed;
  return value;
}

num percentage(Map<String, dynamic> data, String key) {
  final value = data[key];
  if (value is! num || !value.isFinite || value < 0 || value > 100) {
    throw ApiFailure.malformed;
  }
  return value;
}

List<String> lines(Map<String, dynamic> data, String key) =>
    collection(data[key], (value) {
      if (value is! String) throw ApiFailure.malformed;
      return value;
    });

class SavedCheckMetadata {
  SavedCheckMetadata(
    this.id,
    this.checked,
    this.saved,
    this.selectedCount,
    this.resultCount,
  );
  factory SavedCheckMetadata.fromApi(Object? value) {
    final data = object(value);
    return SavedCheckMetadata(
      decimalId(data, 'id'),
      SavedInstant.fromApi(data['checked_at']),
      SavedInstant.fromApi(data['saved_at']),
      count(data, 'selected_symptom_count', min: 1, max: 50),
      count(data, 'displayed_result_count', max: 10),
    );
  }
  final String id;
  final SavedInstant checked, saved;
  final int selectedCount, resultCount;
}

class SavedSymptom {
  SavedSymptom(this.id, this.label);
  factory SavedSymptom.fromApi(Object? value) {
    final data = object(value);
    return SavedSymptom(decimalId(data, 'id'), text(data, 'label'));
  }
  final String id, label;
}

class SavedResult {
  SavedResult(
    this.id,
    this.label,
    this.displayPercent,
    this.matchLevel,
    this.userScore,
    this.coverage,
    this.reason,
    this.explanation,
  );
  factory SavedResult.fromApi(Object? value) {
    final data = object(value);
    return SavedResult(
      decimalId(data, 'id'),
      text(data, 'label'),
      count(data, 'display_percent', max: 100),
      choice(data, 'match_level', {
        'Very High Match',
        'High Match',
        'Moderate Match',
        'Low Match',
        'Weak Match',
      }),
      percentage(data, 'user_match_score'),
      percentage(data, 'coverage'),
      lines(data, 'reason'),
      lines(data, 'explanation'),
    );
  }
  final String id, label, matchLevel;
  final int displayPercent;
  final num userScore, coverage;
  final List<String> reason, explanation;
}

class SavedCheckDetail {
  SavedCheckDetail(
    this.metadata,
    this.version,
    this.checked,
    this.selected,
    this.totalMatches,
    this.displayedMatches,
    this.results,
    this.disclaimer,
  );
  factory SavedCheckDetail.fromApi(Object? value) {
    final data = object(value), snapshot = object(data['snapshot']);
    if (snapshot['version'] is! int || snapshot['version'] != 1) {
      throw const ApiFailure(
        FailureKind.protocol,
        'This saved check version is not supported by this app.',
      );
    }
    final metadata = SavedCheckMetadata.fromApi(data),
        checked = SavedInstant.fromApi(snapshot['checked_at']);
    final selected = collection(snapshot['selected'], SavedSymptom.fromApi),
        results = collection(snapshot['results'], SavedResult.fromApi);
    final summary = object(snapshot['summary']);
    final total = count(summary, 'total_matches'),
        displayed = count(summary, 'displayed_matches', max: 10);
    if (checked.raw != metadata.checked.raw ||
        selected.length != metadata.selectedCount ||
        results.length != metadata.resultCount ||
        displayed != results.length ||
        total < displayed ||
        selected.map((e) => e.id).toSet().length != selected.length) {
      throw ApiFailure.malformed;
    }
    return SavedCheckDetail(
      metadata,
      1,
      checked,
      selected,
      total,
      displayed,
      results,
      text(snapshot, 'disclaimer'),
    );
  }
  final SavedCheckMetadata metadata;
  final int version, totalMatches, displayedMatches;
  final SavedInstant checked;
  final List<SavedSymptom> selected;
  final List<SavedResult> results;
  final String disclaimer;
}
