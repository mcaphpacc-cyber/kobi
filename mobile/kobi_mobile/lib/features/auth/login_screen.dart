import 'dart:convert';

import 'package:flutter/material.dart';

import 'session_controller.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    required this.session,
    required this.environmentName,
  });
  final SessionController session;
  final String environmentName;
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _form = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _passwordFocus = FocusNode();
  bool _obscure = true;

  @override
  void dispose() {
    _email.dispose();
    _password.clear();
    _password.dispose();
    _passwordFocus.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (widget.session.busy ||
        !widget.session.retryAllowed ||
        !_form.currentState!.validate()) {
      return;
    }
    FocusScope.of(context).unfocus();
    final password = _password.text;
    _password.clear();
    await widget.session.login(_email.text, password);
  }

  @override
  Widget build(BuildContext context) {
    final session = widget.session;
    return Scaffold(
      appBar: AppBar(title: const Text('KOBI Mobile')),
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Form(
                  key: _form,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const SizedBox(height: 24),
                      Text(
                        'Welcome to KOBI',
                        style: Theme.of(context).textTheme.headlineMedium,
                      ),
                      const SizedBox(height: 12),
                      const Text(
                        'Sign in with your verified KOBI email and password.',
                      ),
                      if (widget.environmentName != 'production')
                        Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            '${widget.environmentName.toUpperCase()} ENVIRONMENT',
                          ),
                        ),
                      const SizedBox(height: 24),
                      if (session.message != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 20),
                          child: Semantics(
                            liveRegion: true,
                            child: Text(
                              session.message!,
                              key: const Key('session-message'),
                            ),
                          ),
                        ),
                      TextFormField(
                        controller: _email,
                        enabled: !session.busy,
                        decoration: const InputDecoration(labelText: 'Email'),
                        keyboardType: TextInputType.emailAddress,
                        autocorrect: false,
                        enableSuggestions: false,
                        textInputAction: TextInputAction.next,
                        onFieldSubmitted: (_) => _passwordFocus.requestFocus(),
                        validator: (value) {
                          final email = value?.trim() ?? '';
                          return email.isEmpty
                              ? 'Enter your email.'
                              : (!email.contains('@') ||
                                    email.contains(RegExp(r'\s')) ||
                                    utf8.encode(email).length > 255)
                              ? 'Enter a valid email address.'
                              : null;
                        },
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        controller: _password,
                        focusNode: _passwordFocus,
                        enabled: !session.busy,
                        obscureText: _obscure,
                        autocorrect: false,
                        enableSuggestions: false,
                        textInputAction: TextInputAction.done,
                        onFieldSubmitted: (_) => _submit(),
                        decoration: InputDecoration(
                          labelText: 'Password',
                          suffixIcon: IconButton(
                            tooltip: _obscure
                                ? 'Show password'
                                : 'Hide password',
                            onPressed: () =>
                                setState(() => _obscure = !_obscure),
                            icon: Icon(
                              _obscure
                                  ? Icons.visibility
                                  : Icons.visibility_off,
                            ),
                          ),
                        ),
                        validator: (value) => value == null || value.isEmpty
                            ? 'Enter your password.'
                            : utf8.encode(value).length > 1024 ||
                                  value.contains(RegExp(r'[\x00-\x1f\x7f]'))
                            ? 'Check your password.'
                            : null,
                      ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed:
                            session.busy ||
                                session.storageBlocked ||
                                !session.retryAllowed
                            ? null
                            : _submit,
                        child: session.busy
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : Text(
                                session.retryAllowed
                                    ? 'Sign in'
                                    : 'Please wait before retrying',
                              ),
                      ),
                      if (session.storageBlocked)
                        TextButton(
                          onPressed: session.retrySecureCleanup,
                          child: const Text('Retry secure cleanup'),
                        ),
                      const SizedBox(height: 24),
                      const Text(
                        'Mobile foundation preview. Health and knowledge features will follow in later phases.',
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
