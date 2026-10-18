import 'package:http/http.dart' as http;
import 'daily_support.dart';
import 'health_support.dart';

Map<String, Object?> diseaseData([
  String id = '1',
  String slug = 'migraine',
  String name = 'Migraine',
]) => {
  'id': id,
  'slug': slug,
  'name_en': name,
  'name_hi': 'नाम',
  'public_url': 'http://fixture.example.invalid/disease/$slug',
  'body_system': 'Recorded body system',
  'gender': 'both',
};
Map<String, Object?> diseaseDetailData([String slug = 'migraine']) => {
  ...diseaseData('1', slug),
  'locale': 'en',
  'content_format': 'plain_text',
  'sections': {
    'overview':
        'Stored overview <literal text> & punctuation.\n\nSecond recorded paragraph.',
    'causes': null,
    'risk_factors': 'Stored risk factors.',
    'diagnosis': 'Stored diagnosis.',
    'prevention': 'Stored prevention.',
  },
  'references': {
    'overview': [
      {
        'organization': 'Recorded organization',
        'title': 'Recorded source title',
        'url': 'https://source.example.invalid/reference',
        'date': '2026-01-10',
        'date_type': 'Reviewed',
      },
    ],
    'causes': <Object?>[],
    'risk_factors': <Object?>[],
    'diagnosis': <Object?>[],
    'prevention': <Object?>[],
  },
  'causes_items': ['Recorded cause name'],
  'urgency_note': 'Stored urgency note.',
};
Map<String, Object?> substanceData([String name = 'Paracetamol']) => {
  'id': '18446744073709551615',
  'canonical_name': name,
  'registry_url': 'http://fixture.example.invalid/medicines?q=Para',
  'information_type': 'legacy_registry_name',
};
Map<String, Object?> searchMeta(String query, {bool more = false}) => {
  'query': query,
  'limit': 10,
  'has_more': more,
};

class KnowledgeFixture extends DailyFixture {
  KnowledgeFixture() {
    respond = _public;
  }
  Future<http.Response?> Function(http.Request)? answer;
  final publicRequests = <http.Request>[];
  Future<http.Response?> _public(http.Request r) async {
    final path = r.url.path;
    if (path.startsWith('/api/v1/diseases') ||
        path.startsWith('/api/v1/substances')) {
      publicRequests.add(r);
    }
    final custom = await answer?.call(r);
    if (custom != null) return custom;
    if (path.endsWith('/diseases/search')) {
      return healthEnvelope([
        diseaseData('201', 'acne', 'Acne'),
        diseaseData(),
      ], meta: searchMeta(r.url.queryParameters['q']!));
    }
    if (path.endsWith('/substances/search')) {
      return healthEnvelope([
        substanceData(),
      ], meta: searchMeta(r.url.queryParameters['q']!));
    }
    if (path.endsWith('/diseases')) {
      return healthEnvelope([
        diseaseData(),
        diseaseData('2', 'celiac-disease', 'Celiac Disease'),
      ], meta: pageMeta(total: 2));
    }
    if (path.contains('/diseases/')) {
      return healthEnvelope(diseaseDetailData(r.url.pathSegments.last));
    }
    return null;
  }
}
