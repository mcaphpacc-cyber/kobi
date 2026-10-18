import 'package:flutter/material.dart';
import '../features/auth/login_screen.dart';
import '../features/auth/session_controller.dart';
import '../features/knowledge/knowledge_screen.dart';
import '../features/knowledge/knowledge_state.dart';

class AnonymousShell extends StatefulWidget {
  const AnonymousShell({
    super.key,
    required this.session,
    required this.environmentName,
  });
  final SessionController session;
  final String environmentName;
  @override
  State<AnonymousShell> createState() => _AnonymousShellState();
}

class _AnonymousShellState extends State<AnonymousShell> {
  bool _knowledge = false;
  late final KnowledgeController _controller;
  @override
  void initState() {
    super.initState();
    _controller = KnowledgeController(
      KnowledgeRepository(widget.session.repository.api),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    resizeToAvoidBottomInset: false,
    body: _knowledge
        ? Scaffold(
            appBar: AppBar(title: const Text('Knowledge')),
            body: SafeArea(child: KnowledgeArea(controller: _controller)),
          )
        : LoginScreen(
            session: widget.session,
            environmentName: widget.environmentName,
          ),
    bottomNavigationBar: NavigationBar(
      selectedIndex: _knowledge ? 1 : 0,
      onDestinationSelected: (index) => setState(() => _knowledge = index == 1),
      destinations: const [
        NavigationDestination(icon: Icon(Icons.login), label: 'Sign in'),
        NavigationDestination(
          icon: Icon(Icons.menu_book_outlined),
          label: 'Knowledge',
        ),
      ],
    ),
  );
}
