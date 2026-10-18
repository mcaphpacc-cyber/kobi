import 'package:flutter_test/flutter_test.dart';
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/health/paged_state.dart';
import 'package:kobi_mobile/features/knowledge/knowledge_models.dart';
import 'knowledge_support.dart';
import 'health_support.dart';

void main() {
  test(
    'disease summary/catalog preserve string identity, slug and nullable metadata',
    () {
      final value = DiseaseSummary.fromApi({
        ...diseaseData(),
        'name_hi': null,
        'body_system': null,
        'gender': null,
      });
      expect(value.id, '1');
      expect(value.slug, 'migraine');
      expect(value.hindiName, isNull);
      expect(value.bodySystem, isNull);
      expect(value.gender, isNull);
      expect(
        ReadPage<DiseaseSummary>.fromApi(
          [diseaseData()],
          pageMeta(),
          DiseaseSummary.fromApi,
        ).items.length,
        1,
      );
      expect(
        ReadPage<DiseaseSummary>.fromApi(
          [],
          pageMeta(total: 0),
          DiseaseSummary.fromApi,
        ).items,
        isEmpty,
      );
      expect(
        () => DiseaseSummary.fromApi({...diseaseData(), 'slug': '../private'}),
        throwsA(isA<ApiFailure>()),
      );
    },
  );
  test('disease search uses capped search metadata, not invented pages', () {
    final result = SearchResults<DiseaseSummary>.fromApi(
      [diseaseData('2', 'z-name', 'Z'), diseaseData('1', 'a-name', 'A')],
      searchMeta('name', more: true),
      DiseaseSummary.fromApi,
    );
    expect(result.items.map((e) => e.name).join(','), 'Z,A');
    expect(result.hasMore, isTrue);
    expect(
      () => SearchResults<DiseaseSummary>.fromApi(
        [],
        pageMeta(),
        DiseaseSummary.fromApi,
      ),
      throwsA(isA<ApiFailure>()),
    );
    expect(
      () => SearchResults<DiseaseSummary>.fromApi([], {
        ...searchMeta('name'),
        'limit': 20,
      }, DiseaseSummary.fromApi),
      throwsA(isA<ApiFailure>()),
    );
  });
  test(
    'detail preserves five plain-text sections, causes, urgency and exact references',
    () {
      final value = DiseaseDetail.fromApi(diseaseDetailData());
      expect(value.sections.length, 5);
      expect(
        value.sections['overview'],
        'Stored overview <literal text> & punctuation.\n\nSecond recorded paragraph.',
      );
      expect(value.sections['causes'], isNull);
      expect(value.causes.single, 'Recorded cause name');
      expect(value.urgency, 'Stored urgency note.');
      final source = value.references['overview']!.single;
      expect(source.title, 'Recorded source title');
      expect(source.organization, 'Recorded organization');
      expect(source.date, '2026-01-10');
      expect(source.dateType, 'Reviewed');
      expect(source.externalUrl?.scheme, 'https');
    },
  );
  test(
    'optional missing sections/references remain absent rather than invented content',
    () {
      final data = diseaseDetailData()
        ..['sections'] = <String, Object?>{}
        ..['references'] = <String, Object?>{}
        ..['urgency_note'] = null
        ..['causes_items'] = <Object?>[];
      final value = DiseaseDetail.fromApi(data);
      expect(value.sections.values.every((s) => s == null), isTrue);
      expect(value.references.values.every((s) => s.isEmpty), isTrue);
      expect(value.urgency, isNull);
      expect(
        () => DiseaseDetail.fromApi({...data, 'content_format': 'html'}),
        throwsA(isA<ApiFailure>()),
      );
    },
  );
  test(
    'source dates are nullable; only credential-free HTTPS links can open',
    () {
      final base = {
        'organization': 'Organization',
        'title': 'Title',
        'date': null,
        'date_type': null,
      };
      for (final url in [
        'http://source.example.invalid',
        'javascript:alert(1)',
        'https://name:password@source.example.invalid',
      ]) {
        final source = DiseaseSource.fromApi({...base, 'url': url});
        expect(source.url, url);
        expect(source.externalUrl, isNull);
      }
    },
  );
  test('registry names preserve full bigint strings and names only', () {
    final value = RegistryName.fromApi(substanceData());
    expect(value.id, '18446744073709551615');
    expect(value.name, 'Paracetamol');
    expect(
      SearchResults<RegistryName>.fromApi(
        [],
        searchMeta('Para'),
        RegistryName.fromApi,
      ).items,
      isEmpty,
    );
    expect(
      () => RegistryName.fromApi({
        ...substanceData(),
        'information_type': 'curated',
      }),
      throwsA(isA<ApiFailure>()),
    );
    expect(() => RegistryName.fromApi({'id': 1}), throwsA(isA<ApiFailure>()));
  });
}
