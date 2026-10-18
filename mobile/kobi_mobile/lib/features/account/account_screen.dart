import 'dart:convert';

import 'package:flutter/material.dart';

import '../auth/session_controller.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key, required this.session});
  final SessionController session;
  Future<void> _logoutAll(BuildContext context) async {
    final password = await showDialog<String>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const _LogoutAllDialog(),
    );
    if (password != null) await session.logout(currentPassword: password);
  }

  @override
  Widget build(BuildContext context) {
    final identity = session.identity;
    if (identity == null) return const SizedBox.shrink();
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 640),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Your KOBI account',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 24),
              Text('Email: ${identity.email}', key: const Key('account-email')),
              const SizedBox(height: 12),
              Text('Account status: ${identity.status}'),
              const SizedBox(height: 12),
              Text('Email verified: ${identity.emailVerified ? 'Yes' : 'No'}'),
              const SizedBox(height: 12),
              const Text('Authentication state: Signed in'),
              if (session.message != null)
                Padding(
                  padding: const EdgeInsets.only(top: 16),
                  child: Semantics(
                    liveRegion: true,
                    child: Text(session.message!),
                  ),
                ),
              const SizedBox(height: 24),
              OutlinedButton(
                onPressed: session.busy || !session.retryAllowed
                    ? null
                    : session.refreshIdentity,
                child: Text(
                  session.busy ? 'Checking account…' : 'Refresh account',
                ),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: session.busy ? null : session.logout,
                child: const Text('Logout'),
              ),
              const SizedBox(height: 16),
              OutlinedButton(
                onPressed: session.busy ? null : () => _logoutAll(context),
                child: const Text('Logout All'),
              ),
              const SizedBox(height: 12),
              const Text(
                'Logout All ends KOBI API sessions on your devices. Browser sessions are separate.',
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LogoutAllDialog extends StatefulWidget {
  const _LogoutAllDialog();
  @override
  State<_LogoutAllDialog> createState() => _LogoutAllDialogState();
}

class _LogoutAllDialogState extends State<_LogoutAllDialog> {
  final _password = TextEditingController();
  final _form = GlobalKey<FormState>();
  @override
  void dispose() {
    _password.clear();
    _password.dispose();
    super.dispose();
  }

  void _submit() {
    if (!_form.currentState!.validate()) return;
    final value = _password.text;
    _password.clear();
    Navigator.of(context).pop(value);
  }

  @override
  Widget build(BuildContext context) => AlertDialog(
    title: const Text('Logout All'),
    content: SingleChildScrollView(
      child: Form(
        key: _form,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Enter your current password to end all API sessions. This device will sign out even if the server cannot confirm the request.',
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _password,
              obscureText: true,
              autofocus: true,
              autocorrect: false,
              enableSuggestions: false,
              decoration: const InputDecoration(labelText: 'Current password'),
              textInputAction: TextInputAction.done,
              onFieldSubmitted: (_) => _submit(),
              validator: (value) => value == null || value.isEmpty
                  ? 'Enter your current password.'
                  : utf8.encode(value).length > 1024 ||
                        value.contains(RegExp(r'[\x00-\x1f\x7f]'))
                  ? 'Check your password.'
                  : null,
            ),
          ],
        ),
      ),
    ),
    actions: [
      TextButton(
        onPressed: () => Navigator.of(context).pop(),
        child: const Text('Cancel'),
      ),
      FilledButton(
        onPressed: _submit,
        child: const Text('End all API sessions'),
      ),
    ],
  );
}
