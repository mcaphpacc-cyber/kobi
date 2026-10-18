import 'dart:async';

import 'package:flutter/material.dart';

import '../navigation/anonymous_shell.dart';
import '../features/auth/session_controller.dart';
import '../navigation/foundation_shell.dart';

class KobiApp extends StatefulWidget {
  const KobiApp({
    super.key,
    required this.session,
    required this.environmentName,
  });
  final SessionController session;
  final String environmentName;
  @override
  State<KobiApp> createState() => _KobiAppState();
}

class _KobiAppState extends State<KobiApp> with WidgetsBindingObserver {
  bool _privatePreviewHidden = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    setState(() => _privatePreviewHidden = state != AppLifecycleState.resumed);
    if (state == AppLifecycleState.resumed && widget.session.authenticated) {
      unawaited(widget.session.refreshIdentity());
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'KOBI Mobile',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: const Color(0xFF2563EB),
        primary: const Color(0xFF2563EB),
        secondary: const Color(0xFF0F766E),
      ),
      scaffoldBackgroundColor: const Color(0xFFF8FAFC),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size(48, 48),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
        ),
      ),
    ),
    home: ListenableBuilder(
      listenable: widget.session,
      builder: (context, _) {
        final session = widget.session;
        Widget page;
        if (_privatePreviewHidden) {
          page = const Scaffold(body: Center(child: Text('KOBI')));
        } else if (session.state == SessionState.restoring ||
            session.state == SessionState.signingOut) {
          page = Scaffold(
            body: Center(
              child: Semantics(
                label: 'Checking session',
                child: const CircularProgressIndicator(),
              ),
            ),
          );
        } else if (session.state == SessionState.startupError) {
          page = Scaffold(
            appBar: AppBar(title: const Text('KOBI Mobile')),
            body: Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 480),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        session.message ?? 'Unable to check your session.',
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: session.restore,
                        child: const Text('Retry connection'),
                      ),
                      TextButton(
                        onPressed: session.logout,
                        child: const Text('Sign out on this device'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          );
        } else if (session.identity != null &&
            {
              SessionState.authenticated,
              SessionState.checking,
            }.contains(session.state)) {
          page = FoundationShell(
            session: session,
            environmentName: widget.environmentName,
          );
        } else {
          page = AnonymousShell(
            session: session,
            environmentName: widget.environmentName,
          );
        }
        return page;
      },
    ),
  );
}
