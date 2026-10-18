import 'dart:convert';
import 'package:http/http.dart' as http;
import 'daily_support.dart';
import 'health_support.dart';

class CheckoffFixture extends DailyFixture {
  CheckoffFixture() {
    respond = _respond;
  }
  bool includeCapability = true;
  Object? capability = true;
  String name = 'Recorded test medicine';
  final entries = [
    for (final row in [
      dueData(),
      dueData('31', 'taken'),
      dueData('32', 'skipped'),
    ])
      {...row, 'health_medicine_id': '20'},
  ];
  final requests = <http.Request>[];
  Future<http.Response?> Function(http.Request)? override;
  Future<http.Response?> _respond(http.Request request) async {
    requests.add(request);
    final result = await override?.call(request);
    if (result != null) return result;
    if (request.url.path.endsWith('/due-today')) {
      return healthEnvelope(
        entries,
        meta: {
          ...pageMeta(total: entries.length),
          if (includeCapability) 'can_record_checkoffs': capability,
        },
      );
    }
    if (request.url.path.endsWith('/medications')) {
      return healthEnvelope([
        {...medicineData(), 'brand_name': name},
      ]);
    }
    if (request.url.path.endsWith('/checkoff')) {
      final id = request.url.pathSegments[request.url.pathSegments.length - 2];
      final index = entries.indexWhere((row) => row['id'] == id);
      final state = (jsonDecode(request.body) as Map)['state'];
      entries[index] = {...entries[index], 'state': state};
      return healthEnvelope(entries[index]);
    }
    return null;
  }

  List<http.Request> get posts => requests
      .where((r) => r.method == 'POST' && r.url.path.endsWith('/checkoff'))
      .toList();
}
