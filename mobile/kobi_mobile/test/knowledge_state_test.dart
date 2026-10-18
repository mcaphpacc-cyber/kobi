import 'dart:async';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/core/errors/api_failure.dart';
import 'package:kobi_mobile/features/knowledge/knowledge_state.dart';
import 'knowledge_support.dart';
import 'health_support.dart';

void main() {
  KnowledgeController controller(KnowledgeFixture f) {
    final c = KnowledgeController(KnowledgeRepository(f.h.api));
    addTearDown(() {
      c.dispose();
      f.dispose();
    });
    return c;
  }

  test(
    'catalog loads incrementally with actual query and server order',
    () async {
      final f = KnowledgeFixture();
      final c = controller(f);
      await c.catalog.load();
      expect(c.catalog.items.length, 2);
      expect(c.catalog.items.first.slug, 'migraine');
    },
  );
  test(
    'catalog pagination, empty/error/retry and unexpected page are distinct',
    () async {
      final f = KnowledgeFixture();
      final vm = controller(f);
      f.answer = (r) async {
        if (!r.url.path.endsWith('/diseases')) return null;
        expect(r.url.queryParameters.keys.toSet(), {'page', 'page_size'});
        expect(r.url.queryParameters['page_size'], '20');
        final second = r.url.queryParameters['page'] == '2';
        return healthEnvelope([
          diseaseData(second ? '2' : '1', second ? 'acne' : 'migraine'),
        ], meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second));
      };
      await vm.catalog.load();
      await vm.catalog.load(more: true);
      expect(vm.catalog.items.map((e) => e.id).join(','), '1,2');
      f.answer = (r) async => r.url.path.endsWith('/diseases')
          ? healthEnvelope([], meta: pageMeta(total: 0))
          : null;
      await vm.catalog.load();
      expect(vm.catalog.initialized, isTrue);
      expect(vm.catalog.items, isEmpty);
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases') ? healthError(500) : null;
      await vm.catalog.load();
      expect(vm.catalog.initialized, isFalse);
      expect(vm.catalog.request.error?.status, 500);
      f.answer = null;
      await vm.catalog.load();
      expect(vm.catalog.items.length, 2);
      f.answer = (r) async =>
          healthEnvelope([], meta: pageMeta(page: 2, total: 0));
      await vm.catalog.load();
      expect(vm.catalog.request.error?.kind, FailureKind.protocol);
    },
  );
  test(
    'public detail loading/not found/network/error/retry does not change login state',
    () async {
      final f = KnowledgeFixture();
      final c = controller(f);
      await f.ready();
      final flight = c.open('migraine');
      expect(c.detail.loading, isTrue);
      await flight;
      expect(c.detail.value?.disease.slug, 'migraine');
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases/missing') ? healthError(404) : null;
      await c.open('missing');
      expect(c.detail.error?.status, 404);
      expect(f.h.session.authenticated, isTrue);
      f.answer = (r) async {
        if (r.url.path.contains('/diseases/')) {
          throw const SocketException('synthetic');
        }
        return null;
      };
      await c.open('migraine');
      expect(c.detail.error?.kind, FailureKind.network);
      f.answer = null;
      await c.open('migraine');
      expect(c.detail.value, isNotNull);
      expect(f.paths.where((p) => p == '/auth/refresh'), isEmpty);
      for (final request in f.publicRequests) {
        expect(request.headers.containsKey('Authorization'), isFalse);
        expect(request.headers.containsKey('Cookie'), isFalse);
      }
    },
  );
  for (final substances in [false, true]) {
    testWidgets(
      '${substances ? 'registry' : 'disease'} search distinguishes empty, server, network and malformed responses',
      (tester) async {
        final f = KnowledgeFixture();
        final vm = controller(f);
        f.answer = (r) async => healthEnvelope([], meta: searchMeta('test'));
        if (substances) {
          vm.substances.change('test');
        } else {
          vm.diseases.change('test');
        }
        await tester.pump(const Duration(milliseconds: 350));
        await tester.pump();
        expect(
          substances
              ? vm.substances.result.value?.items.isEmpty
              : vm.diseases.result.value?.items.isEmpty,
          isTrue,
        );
        f.answer = (r) async => healthError(500);
        if (substances) {
          await vm.substances.retry();
        } else {
          await vm.diseases.retry();
        }
        expect(
          substances
              ? vm.substances.result.error?.status
              : vm.diseases.result.error?.status,
          500,
        );
        f.answer = (r) async {
          throw const SocketException('synthetic');
        };
        if (substances) {
          await vm.substances.retry();
        } else {
          await vm.diseases.retry();
        }
        expect(
          substances
              ? vm.substances.result.error?.kind
              : vm.diseases.result.error?.kind,
          FailureKind.network,
        );
        f.answer = (r) async =>
            healthEnvelope(<String, Object?>{}, meta: searchMeta('test'));
        if (substances) {
          await vm.substances.retry();
        } else {
          await vm.diseases.retry();
        }
        expect(
          substances
              ? vm.substances.result.error?.kind
              : vm.diseases.result.error?.kind,
          FailureKind.protocol,
        );
        f.answer = null;
        if (substances) {
          await vm.substances.retry();
        } else {
          await vm.diseases.retry();
        }
        expect(
          substances
              ? vm.substances.result.value?.items.isNotEmpty
              : vm.diseases.result.value?.items.isNotEmpty,
          isTrue,
        );
      },
    );
    testWidgets(
      '${substances ? 'substance' : 'disease'} debounce prevents keystroke requests and clear cancels pending search',
      (tester) async {
        final f = KnowledgeFixture();
        final c = controller(f);
        if (substances) {
          c.substances.change('p');
          c.substances.change('pa');
          c.substances.change('para');
        } else {
          c.diseases.change('a');
          c.diseases.change('ac');
          c.diseases.change('acne');
        }
        await tester.pump(const Duration(milliseconds: 349));
        expect(f.publicRequests, isEmpty);
        await tester.pump(const Duration(milliseconds: 1));
        await tester.pump();
        expect(f.publicRequests.length, 1);
        if (substances) {
          expect(c.substances.result.value?.items.length, 1);
          c.substances.change('partial');
          c.substances.change('');
        } else {
          expect(c.diseases.result.value?.items.length, 2);
          c.diseases.change('partial');
          c.diseases.change('');
        }
        await tester.pump(const Duration(milliseconds: 400));
        expect(f.publicRequests.length, 1);
      },
    );
    testWidgets(
      '${substances ? 'substance' : 'disease'} acn response arrives after acne; latest query wins',
      (tester) async {
        final f = KnowledgeFixture();
        final vm = controller(f);
        final old = Completer<http.Response>();
        f.answer = (r) async {
          if (r.url.queryParameters['q'] == 'acn') return old.future;
          return null;
        };
        if (substances) {
          vm.substances.change('acn');
        } else {
          vm.diseases.change('acn');
        }
        await tester.pump(const Duration(milliseconds: 350));
        if (substances) {
          vm.substances.change('acne');
        } else {
          vm.diseases.change('acne');
        }
        await tester.pump(const Duration(milliseconds: 350));
        await tester.pump();
        old.complete(
          healthEnvelope(
            substances
                ? [substanceData('Old result')]
                : [diseaseData('9', 'old-result', 'Old result')],
            meta: searchMeta('acn'),
          ),
        );
        await tester.pump();
        expect(
          substances
              ? vm.substances.result.value?.query
              : vm.diseases.result.value?.query,
          'acne',
        );
        expect(
          substances
              ? vm.substances.result.value?.items.first.name
              : vm.diseases.result.value?.items.first.name,
          isNot('Old result'),
        );
      },
    );
  }
  testWidgets(
    'encoded punctuation/Unicode query uses q only, with empty/error/retry states',
    (tester) async {
      final f = KnowledgeFixture();
      final vm = controller(f);
      const query = 'P%_=& नाम';
      Map<String, String>? sentQuery;
      f.answer = (r) async {
        sentQuery = r.url.queryParameters;
        return healthEnvelope([], meta: searchMeta(query));
      };
      vm.substances.change(query);
      await tester.pump(const Duration(milliseconds: 350));
      await tester.pump();
      expect(sentQuery, {'q': query});
      expect(vm.substances.result.value?.items, isEmpty);
      f.answer = (r) async => healthError(500);
      await vm.substances.retry();
      expect(vm.substances.result.error?.status, 500);
      f.answer = null;
      await vm.substances.retry();
      expect(vm.substances.result.value?.items.length, 1);
    },
  );
  test(
    'invalid slug rejected before request; newer detail supersedes pending detail',
    () async {
      final f = KnowledgeFixture();
      final vm = controller(f);
      await vm.open('a/b');
      expect(f.publicRequests, isEmpty);
      expect(vm.detail.error?.kind, FailureKind.protocol);
      final old = Completer<http.Response>();
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases/migraine') ? old.future : null;
      final flight = vm.open('migraine');
      await Future<void>.delayed(Duration.zero);
      await vm.open('acne');
      old.complete(healthEnvelope(diseaseDetailData()));
      await flight;
      expect(vm.detail.value?.disease.slug, 'acne');
    },
  );
  testWidgets('public 401/429 never refresh/end auth; Retry-After is honored', (
    tester,
  ) async {
    final f = KnowledgeFixture();
    final vm = controller(f);
    await f.ready();
    f.answer = (r) async => healthError(401);
    await vm.open('migraine');
    expect(f.h.session.authenticated, isTrue);
    f.answer = (r) async => healthError(429, retry: 30);
    vm.diseases.change('acne');
    await tester.pump(const Duration(milliseconds: 350));
    await tester.pump();
    final calls = f.publicRequests.length;
    await vm.diseases.retry();
    expect(f.publicRequests.length, calls);
    expect(vm.diseases.result.retryAllowed, isFalse);
    expect(f.paths.where((p) => p == '/auth/refresh'), isEmpty);
    vm.diseases.result
        .clear(); // Cancel the cooldown timer before widget invariants.
  });
}
