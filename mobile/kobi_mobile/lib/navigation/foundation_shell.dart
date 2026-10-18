import 'dart:async';
import 'package:flutter/material.dart';
import '../features/account/account_screen.dart';
import '../features/auth/session_controller.dart';
import '../features/health/health_repository.dart';
import '../features/health/health_state.dart';
import '../features/health/health_screens.dart';
import '../features/daily_health/daily_health_state.dart';
import '../features/daily_health/daily_health_screen.dart';
import '../features/saved_checks/saved_state.dart';
import '../features/saved_checks/saved_screen.dart';
import '../features/knowledge/knowledge_state.dart';
import '../features/knowledge/knowledge_screen.dart';

class FoundationShell extends StatefulWidget {
  const FoundationShell({
    super.key,
    required this.session,
    required this.environmentName,
  });
  final SessionController session;
  final String environmentName;
  @override
  State<FoundationShell> createState() => _FoundationShellState();
}

class _FoundationShellState extends State<FoundationShell> {
  late final ProfileController _profiles;
  HealthScope? _scope;
  DailyHealthScope? _daily;
  late final SavedChecksController _saved;
  late final KnowledgeController _knowledge;
  String? _savedId;
  HealthPage? _dailyReturn;
  int _index = 0;
  final List<HealthRoute> _routes = [];
  @override
  void initState() {
    super.initState();
    _profiles = ProfileController(
      HealthRepository(widget.session.repository.api, widget.session),
    );
    _saved = SavedChecksController(SavedCheckRepository(widget.session));
    _knowledge = KnowledgeController(
      KnowledgeRepository(widget.session.repository.api),
    );
    _profiles.addListener(_profileChanged);
    unawaited(_profiles.refresh());
  }

  void _profileChanged() {
    final selected = _profiles.selected;
    if (!identical(_scope?.profile, selected)) {
      if (selected == null &&
          _index == 1 &&
          {
            HealthPage.schedules,
            HealthPage.dueToday,
          }.contains(_routes.lastOrNull?.page)) {
        _dailyReturn = _routes.last.page;
      }
      _daily?.dispose();
      _scope?.dispose();
      _scope = selected == null ? null : HealthScope(_profiles, selected);
      _daily = _scope == null ? null : DailyHealthScope(_scope!);
      _routes.clear();
      if (_scope case final HealthScope scope) unawaited(scope.loadSummary());
      if (selected != null && _dailyReturn != null) {
        if (_index == 1) {
          _routes.add(HealthRoute(_dailyReturn!));
          unawaited(
            _dailyReturn == HealthPage.schedules
                ? _daily!.schedules.load()
                : _daily!.due.load(),
          );
        }
        _dailyReturn = null;
      }
    }
    if (mounted) setState(() {});
  }

  void _open(HealthRoute route) {
    if (route.page == HealthPage.savedChecks) {
      _openSaved();
      return;
    }
    final scope = _scope;
    if (scope == null) return;
    setState(() {
      _index = 1;
      _routes.add(route);
    });
    switch (route.page) {
      case HealthPage.summary:
        unawaited(scope.loadSummary());
      case HealthPage.conditions:
        unawaited(scope.conditions.load());
      case HealthPage.medicines:
        unawaited(scope.loadMedicines());
      case HealthPage.condition:
        unawaited(scope.loadCondition(route.id!));
      case HealthPage.medicine:
        unawaited(scope.loadMedicine(route.id!));
      case HealthPage.profile:
      case HealthPage.savedChecks:
        break;
      case HealthPage.schedules:
        unawaited(_daily!.schedules.load());
      case HealthPage.dueToday:
        unawaited(_daily!.due.load());
    }
  }

  void _openSaved() {
    setState(() {
      _index = 2;
      _savedId = null;
    });
    unawaited(_saved.list.load());
  }

  void _back() => setState(() {
    if (_index == 2) {
      _savedId = null;
    } else {
      _routes.removeLast();
    }
  });
  @override
  void dispose() {
    _profiles.removeListener(_profileChanged);
    _daily?.dispose();
    _scope?.dispose();
    _saved.dispose();
    _knowledge.dispose();
    _profiles.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scope = _scope;
    final canBack =
        (_index == 1 && _routes.isNotEmpty) ||
        (_index == 2 && _savedId != null);
    return PopScope(
      canPop: !canBack,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && canBack) _back();
      },
      child: Scaffold(
        appBar: AppBar(
          leading: canBack ? BackButton(onPressed: _back) : null,
          title: Text(
            _index == 3
                ? 'Account'
                : _index == 4
                ? 'Knowledge'
                : _index == 2
                ? 'Saved'
                : _index == 0
                ? 'KOBI Home'
                : 'Health',
          ),
          bottom: widget.environmentName == 'production'
              ? null
              : PreferredSize(
                  preferredSize: const Size.fromHeight(24),
                  child: Text(
                    '${widget.environmentName.toUpperCase()} ENVIRONMENT',
                  ),
                ),
        ),
        body: SafeArea(
          child: _index == 3
              ? AccountScreen(session: widget.session)
              : _index == 4
              ? KnowledgeArea(controller: _knowledge)
              : _index == 2
              ? SavedChecksScreen(
                  controller: _saved,
                  detailId: _savedId,
                  open: (id) {
                    setState(() => _savedId = id);
                    unawaited(_saved.open(id));
                  },
                )
              : scope == null
              ? ProfileSelector(controller: _profiles)
              : HealthContent(
                  key: ValueKey(_profiles.revision),
                  scope: scope,
                  route: _index == 0
                      ? null
                      : _routes.lastOrNull ??
                            const HealthRoute(HealthPage.profile),
                  open: _open,
                  changeProfile: _profiles.choose,
                  dailyContent: _daily == null
                      ? null
                      : DailyHealthScreen(
                          scope: _daily!,
                          dueToday:
                              _routes.lastOrNull?.page == HealthPage.dueToday,
                        ),
                ),
        ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _index,
          onDestinationSelected: (value) {
            if (value == 2) {
              _openSaved();
              return;
            }
            setState(() {
              _index = value;
              _routes.clear();
            });
            if (value == 0 && _scope != null) unawaited(_scope!.loadSummary());
          },
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.home_outlined),
              label: 'Home',
            ),
            NavigationDestination(
              icon: Icon(Icons.favorite_outline),
              label: 'Health',
            ),
            NavigationDestination(
              icon: Icon(Icons.bookmark_outline),
              label: 'Saved',
            ),
            NavigationDestination(
              icon: Icon(Icons.person_outline),
              label: 'Account',
            ),
            NavigationDestination(
              icon: Icon(Icons.menu_book_outlined),
              label: 'Knowledge',
            ),
          ],
        ),
      ),
    );
  }
}
