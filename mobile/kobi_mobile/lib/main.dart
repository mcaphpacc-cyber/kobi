import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/io_client.dart';

import 'app/environment.dart';
import 'app/kobi_app.dart';
import 'core/network/api_client.dart';
import 'core/storage/session_store.dart';
import 'features/auth/auth_repository.dart';
import 'features/auth/session_controller.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    final environment = Environment.fromBuild();
    if (!Platform.isAndroid && !Platform.isIOS) {
      throw const FormatException('Android/iOS required.');
    }
    final native = HttpClient()
      ..connectionTimeout = const Duration(seconds: 10);
    final api = ApiClient(environment, IOClient(native));
    final session = SessionController(
      AuthRepository(api),
      SecureSessionStore(environment.scope),
    );
    runApp(KobiApp(session: session, environmentName: environment.name));
    unawaited(session.restore());
  } on FormatException {
    runApp(
      const MaterialApp(
        home: Scaffold(
          body: SafeArea(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Center(
                child: Text(
                  'KOBI Mobile needs a configured HTTPS API environment and an Android or iOS device. See the mobile setup instructions.',
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
