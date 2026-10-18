import '../../core/errors/api_failure.dart';
import '../health/health_models.dart';

class DiseaseSummary {
  DiseaseSummary(
    this.id,
    this.slug,
    this.name,
    this.hindiName,
    this.publicUrl,
    this.bodySystem,
    this.gender,
  );
  factory DiseaseSummary.fromApi(Object? value) {
    final data = object(value), slug = text(object(value), 'slug');
    if (slug.length > 255 ||
        !RegExp(r'^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$').hasMatch(slug)) {
      throw ApiFailure.malformed;
    }
    final gender = optionalText(data, 'gender');
    if (gender != null && !{'male', 'female', 'both'}.contains(gender)) {
      throw ApiFailure.malformed;
    }
    return DiseaseSummary(
      decimalId(data, 'id'),
      slug,
      text(data, 'name_en'),
      optionalText(data, 'name_hi'),
      text(data, 'public_url'),
      optionalText(data, 'body_system'),
      gender,
    );
  }
  final String id, slug, name, publicUrl;
  final String? hindiName, bodySystem, gender;
}

class DiseaseSource {
  DiseaseSource(
    this.organization,
    this.title,
    this.url,
    this.date,
    this.dateType,
  );
  factory DiseaseSource.fromApi(Object? value) {
    final data = object(value);
    return DiseaseSource(
      text(data, 'organization'),
      text(data, 'title'),
      text(data, 'url'),
      optionalText(data, 'date'),
      optionalText(data, 'date_type'),
    );
  }
  final String organization, title, url;
  final String? date, dateType;
  Uri? get externalUrl {
    final uri = Uri.tryParse(url);
    return uri != null &&
            uri.scheme == 'https' &&
            uri.host.isNotEmpty &&
            uri.userInfo.isEmpty
        ? uri
        : null;
  }
}

class DiseaseDetail {
  DiseaseDetail(
    this.disease,
    this.sections,
    this.references,
    this.causes,
    this.urgency,
  );
  factory DiseaseDetail.fromApi(Object? value) {
    final data = object(value);
    if (data['locale'] != 'en' || data['content_format'] != 'plain_text') {
      throw ApiFailure.malformed;
    }
    final sections = object(data['sections']),
        references = object(data['references']);
    final parsed = <String, String?>{},
        sources = <String, List<DiseaseSource>>{};
    for (final key in sectionLabels.keys) {
      parsed[key] = sections[key] == null ? null : text(sections, key);
      sources[key] = collection(
        references[key] ?? <Object?>[],
        DiseaseSource.fromApi,
      );
    }
    return DiseaseDetail(
      DiseaseSummary.fromApi(data),
      Map.unmodifiable(parsed),
      Map.unmodifiable(sources),
      collection(data['causes_items'], (item) {
        if (item is! String) throw ApiFailure.malformed;
        return item;
      }),
      optionalText(data, 'urgency_note'),
    );
  }
  static const sectionLabels = {
    'overview': 'Overview',
    'causes': 'Causes',
    'risk_factors': 'Risk Factors',
    'diagnosis': 'Diagnosis',
    'prevention': 'Prevention',
  };
  final DiseaseSummary disease;
  final Map<String, String?> sections;
  final Map<String, List<DiseaseSource>> references;
  final List<String> causes;
  final String? urgency;
}

class RegistryName {
  RegistryName(this.id, this.name, this.registryUrl);
  factory RegistryName.fromApi(Object? value) {
    final data = object(value);
    if (data['information_type'] != 'legacy_registry_name') {
      throw ApiFailure.malformed;
    }
    return RegistryName(
      decimalId(data, 'id'),
      text(data, 'canonical_name'),
      text(data, 'registry_url'),
    );
  }
  final String id, name, registryUrl;
}

class SearchResults<T> {
  SearchResults(this.items, this.query, this.hasMore);
  factory SearchResults.fromApi(
    Object data,
    Map<String, dynamic> meta,
    T Function(Object?) parse,
  ) {
    if (meta['query'] is! String ||
        meta['limit'] is! int ||
        meta['limit'] != 10 ||
        meta['has_more'] is! bool) {
      throw ApiFailure.malformed;
    }
    final items = collection(data, parse);
    if (items.length > 10) throw ApiFailure.malformed;
    return SearchResults(
      items,
      meta['query'] as String,
      meta['has_more'] as bool,
    );
  }
  final List<T> items;
  final String query;
  final bool hasMore;
}
