import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:kobi_mobile/app/kobi_app.dart';
import 'knowledge_support.dart';
import 'health_support.dart';

void main() {
  Future<KnowledgeFixture> mount(
    WidgetTester tester, {
    KnowledgeFixture? fixture,
    bool authenticated = false,
    Size size = const Size(390, 844),
    double scale = 1,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    tester.platformDispatcher.textScaleFactorTestValue = scale;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
    final f = fixture ?? KnowledgeFixture();
    if (!authenticated) f.h.store.bundle = null;
    await f.ready();
    await tester.pumpWidget(
      KobiApp(session: f.h.session, environmentName: 'local'),
    );
    await tester.pumpAndSettle();
    addTearDown(() async {
      await tester.pumpWidget(const SizedBox());
      f.dispose();
    });
    return f;
  }

  Future<void> tap(WidgetTester tester, String text) async {
    final target = find.text(text).last;
    await tester.ensureVisible(target);
    await tester.tap(target);
    await tester.pumpAndSettle();
  }

  Future<void> back(WidgetTester tester) async {
    await tester.ensureVisible(find.byType(BackButton));
    await tester.tap(find.byType(BackButton));
    await tester.pumpAndSettle();
  }

  Future<void> search(WidgetTester tester, String text) async {
    await tester.enterText(find.byType(TextField), text);
    await tester.pump(const Duration(milliseconds: 350));
    await tester.pumpAndSettle();
  }

  for (final size in [const Size(390, 844), const Size(768, 1024)]) {
    testWidgets(
      'anonymous disease/search/detail/back/registry journey at $size',
      (tester) async {
        final f = await mount(tester, size: size);
        expect(find.text('Welcome to KOBI'), findsOneWidget);
        await tap(tester, 'Knowledge');
        await tap(tester, 'Diseases');
        expect(find.text('Disease Catalog'), findsOneWidget);
        expect(find.text('Migraine'), findsOneWidget);
        await tap(tester, 'Migraine');
        expect(find.text('Overview'), findsOneWidget);
        expect(find.text('Diagnosis'), findsOneWidget);
        expect(
          find.text('Stored overview <literal text> & punctuation.'),
          findsOneWidget,
        );
        expect(find.text('Second recorded paragraph.'), findsOneWidget);
        expect(find.text('Stored urgency note.'), findsOneWidget);
        expect(find.text('Sources'), findsOneWidget);
        expect(find.text('Reviewed: 2026-01-10'), findsOneWidget);
        await tester.ensureVisible(find.text('Stored prevention.'));
        expect(find.text('Stored prevention.').hitTestable(), findsOneWidget);
        await back(tester);
        await search(tester, 'acn');
        expect(find.text('Acne'), findsOneWidget);
        await tap(tester, 'Acne');
        expect(find.text('Content language: English'), findsOneWidget);
        await back(tester);
        expect(
          tester.widget<TextField>(find.byType(TextField)).controller?.text,
          'acn',
        );
        expect(find.text('Acne'), findsOneWidget);
        await tester.tap(find.byTooltip('Clear search'));
        await tester.pumpAndSettle();
        expect(find.text('Celiac Disease'), findsOneWidget);
        await back(tester);
        await tap(tester, 'Medicine / Substance Registry');
        await search(tester, 'Para');
        expect(find.text('Paracetamol'), findsOneWidget);
        expect(
          find.textContaining('Legacy registry/substance information'),
          findsOneWidget,
        );
        expect(find.text('18446744073709551615'), findsNothing);
        expect(find.text('Dose'), findsNothing);
        expect(find.text('Side effects'), findsNothing);
        expect(f.h.session.identity, isNull);
        expect(f.paths.where((p) => p.startsWith('/auth/')), isEmpty);
        expect(
          f.publicRequests.every(
            (r) => !r.headers.containsKey('Authorization'),
          ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'authenticated knowledge keeps session and returns to all private areas',
    (tester) async {
      final f = await mount(tester, authenticated: true);
      await tap(tester, 'Knowledge');
      await tap(tester, 'Diseases');
      await tap(tester, 'Migraine');
      expect(f.h.session.authenticated, isTrue);
      expect(f.paths.where((p) => p == '/auth/refresh'), isEmpty);
      await tap(tester, 'Home');
      expect(find.text('Health Dashboard'), findsOneWidget);
      await tap(tester, 'Medical Summary');
      expect(find.text('Active / chronic conditions: 1'), findsOneWidget);
      await tap(tester, 'View Conditions');
      await tap(tester, 'Fixture condition 10');
      expect(find.text('Condition Detail'), findsOneWidget);
      await tap(tester, 'Home');
      await tap(tester, 'Personal Medicines');
      await tap(tester, 'Fixture medicine 20');
      expect(find.text('Medicine Detail'), findsOneWidget);
      await tap(tester, 'Home');
      await tap(tester, 'Medication Schedule');
      expect(find.text('Active'), findsOneWidget);
      await tap(tester, 'Home');
      await tap(tester, 'Due Today');
      expect(find.text('Taken'), findsOneWidget);
      await tap(tester, 'Saved');
      await tester.tap(find.byType(ListTile).last);
      await tester.pumpAndSettle();
      expect(find.text('Historical snapshot'), findsOneWidget);
      await tap(tester, 'Health');
      expect(find.text('Health Profile'), findsOneWidget);
      await tap(tester, 'Account');
      expect(find.text('Your KOBI account'), findsOneWidget);
      await tap(tester, 'Logout');
      expect(find.text('Welcome to KOBI'), findsOneWidget);
      await tap(tester, 'Knowledge');
      expect(find.text('Public Knowledge'), findsOneWidget);
      expect(find.text('Fixture A'), findsNothing);
      expect(find.text('Historical snapshot'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets('catalog paging uses Load more and preserves server order', (
    tester,
  ) async {
    final f = KnowledgeFixture();
    f.answer = (r) async {
      if (!r.url.path.endsWith('/diseases')) return null;
      final second = r.url.queryParameters['page'] == '2';
      return healthEnvelope([
        second ? diseaseData('201', 'acne', 'Acne') : diseaseData(),
      ], meta: pageMeta(page: second ? 2 : 1, total: 21, more: !second));
    };
    await mount(tester, fixture: f);
    await tap(tester, 'Knowledge');
    await tap(tester, 'Diseases');
    await tap(tester, 'Load more');
    expect(find.text('Migraine'), findsOneWidget);
    expect(find.text('Acne'), findsOneWidget);
    expect(find.text('Load more'), findsNothing);
  });
  testWidgets(
    'catalog and detail safe errors retry and 404 stay in public knowledge',
    (tester) async {
      final f = KnowledgeFixture();
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases') ? healthError(500) : null;
      await mount(tester, fixture: f);
      await tap(tester, 'Knowledge');
      await tap(tester, 'Diseases');
      expect(
        find.text('The service is unavailable. Please try again later.'),
        findsOneWidget,
      );
      expect(find.textContaining('Private internal'), findsNothing);
      f.answer = null;
      await tap(tester, 'Retry');
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases/migraine') ? healthError(404) : null;
      await tap(tester, 'Migraine');
      expect(find.text('Disease not found'), findsOneWidget);
      expect(find.text('Welcome to KOBI'), findsNothing);
      await tap(tester, 'Back to diseases');
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases/migraine') ? healthError(503) : null;
      await tap(tester, 'Migraine');
      expect(
        find.text('The service is unavailable. Please try again later.'),
        findsOneWidget,
      );
      f.answer = null;
      await tap(tester, 'Retry');
      expect(find.text('Overview'), findsOneWidget);
    },
  );
  testWidgets('empty catalog and capped searches have distinct useful states', (
    tester,
  ) async {
    final f = KnowledgeFixture();
    f.answer = (r) async {
      if (r.url.path.endsWith('/diseases')) {
        return healthEnvelope([], meta: pageMeta(total: 0));
      }
      if (r.url.path.endsWith('/search')) {
        return healthEnvelope(
          [],
          meta: searchMeta(r.url.queryParameters['q']!),
        );
      }
      return null;
    };
    await mount(tester, fixture: f);
    await tap(tester, 'Knowledge');
    await tap(tester, 'Diseases');
    expect(find.text('No public diseases are available.'), findsOneWidget);
    await search(tester, 'missing');
    expect(find.text('No matching diseases found.'), findsOneWidget);
    f.answer = (r) async => r.url.path.endsWith('/diseases/search')
        ? healthEnvelope([
            diseaseData(),
          ], meta: searchMeta(r.url.queryParameters['q']!, more: true))
        : null;
    await search(tester, 'partial');
    expect(
      find.text('More matches may be available. Refine your search.'),
      findsOneWidget,
    );
    expect(find.text('Load more'), findsNothing);
    await back(tester);
    await tap(tester, 'Medicine / Substance Registry');
    f.answer = (r) async =>
        healthEnvelope([], meta: searchMeta(r.url.queryParameters['q']!));
    await search(tester, 'missing');
    expect(
      find.text('No matching substances found in KOBI’s current registry.'),
      findsOneWidget,
    );
  });
  testWidgets(
    'public 401 never logs out authenticated account or navigates to Login',
    (tester) async {
      final f = await mount(tester, authenticated: true);
      f.answer = (r) async =>
          r.url.path.contains('/diseases/') ? healthError(401) : null;
      await tap(tester, 'Knowledge');
      await tap(tester, 'Diseases');
      await tap(tester, 'Migraine');
      expect(
        find.text('Public information is unavailable. Please try again later.'),
        findsOneWidget,
      );
      expect(f.h.session.authenticated, isTrue);
      expect(f.paths.where((p) => p == '/auth/refresh'), isEmpty);
      await tap(tester, 'Account');
      expect(find.text('Your KOBI account'), findsOneWidget);
    },
  );
  for (final substance in [false, true]) {
    testWidgets(
      'latest ${substance ? 'registry' : 'disease'} query remains when old response arrives',
      (tester) async {
        final f = KnowledgeFixture();
        final old = Completer<http.Response>();
        f.answer = (r) async =>
            r.url.queryParameters['q'] == 'acn' ? old.future : null;
        await mount(tester, fixture: f);
        await tap(tester, 'Knowledge');
        await tap(
          tester,
          substance ? 'Medicine / Substance Registry' : 'Diseases',
        );
        await tester.enterText(find.byType(TextField), 'acn');
        await tester.pump(const Duration(milliseconds: 350));
        expect(find.byType(CircularProgressIndicator), findsOneWidget);
        await search(tester, 'acne');
        old.complete(
          healthEnvelope(
            substance
                ? [substanceData('Old result')]
                : [diseaseData('9', 'old-result', 'Old result')],
            meta: searchMeta('acn'),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Old result'), findsNothing);
        expect(find.text(substance ? 'Paracetamol' : 'Acne'), findsOneWidget);
      },
    );
  }
  testWidgets(
    'detail loading is deliberate and optional sections are not invented',
    (tester) async {
      final f = await mount(tester);
      final delayed = Completer<http.Response>();
      await tap(tester, 'Knowledge');
      await tap(tester, 'Diseases');
      f.answer = (r) async =>
          r.url.path.endsWith('/diseases/migraine') ? delayed.future : null;
      await tester.tap(find.text('Migraine'));
      await tester.pump();
      expect(find.byType(CircularProgressIndicator), findsOneWidget);
      delayed.complete(
        healthEnvelope(
          diseaseDetailData()
            ..['sections'] = <String, Object?>{}
            ..['references'] = <String, Object?>{}
            ..['causes_items'] = <Object?>[],
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('Section text is not available for this disease.'),
        findsOneWidget,
      );
      expect(find.text('Treatments'), findsNothing);
    },
  );
  testWidgets(
    'reference opens externally with no headers; launcher failure is safe',
    (tester) async {
      final calls = <MethodCall>[];
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(
            const MethodChannel('plugins.flutter.io/url_launcher'),
            (call) async {
              calls.add(call);
              return false;
            },
          );
      addTearDown(
        () => TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
            .setMockMethodCallHandler(
              const MethodChannel('plugins.flutter.io/url_launcher'),
              null,
            ),
      );
      await mount(tester);
      await tap(tester, 'Knowledge');
      await tap(tester, 'Diseases');
      await tap(tester, 'Migraine');
      await tap(tester, 'Open source in browser');
      expect(calls.length, 1);
      final args = calls.single.arguments as Map;
      expect(args['url'], 'https://source.example.invalid/reference');
      expect(args['headers'], isEmpty);
      expect(args['useWebView'], isFalse);
      expect(args['useSafariVC'], isFalse);
      expect(
        find.text('Unable to open this source. You can copy its URL below.'),
        findsOneWidget,
      );
    },
  );
  testWidgets('long content and sources wrap at large text on phone', (
    tester,
  ) async {
    final f = KnowledgeFixture();
    f.answer = (r) async {
      if (!r.url.path.endsWith('/diseases/migraine')) return null;
      final data = diseaseDetailData();
      (data['sections'] as Map<String, Object?>)['overview'] = List.filled(
        80,
        'Unmodified recorded paragraph with readable spacing.',
      ).join('\n\n');
      return healthEnvelope(data);
    };
    await mount(tester, fixture: f, authenticated: true, scale: 1.6);
    await tap(tester, 'Knowledge');
    await tap(tester, 'Diseases');
    await tap(tester, 'Migraine');
    await tester.ensureVisible(find.text('Stored prevention.'));
    expect(find.text('Stored prevention.').hitTestable(), findsOneWidget);
    expect(tester.takeException(), isNull);
    expect(
      tester
          .renderObject<RenderParagraph>(find.text('Knowledge').last)
          .didExceedMaxLines,
      isFalse,
    );
    await tap(tester, 'Health');
    expect(find.text('Health Profile'), findsOneWidget);
  });
}
