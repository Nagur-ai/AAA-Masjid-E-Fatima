import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../api_client.dart';
import '../app_state.dart';
import '../models.dart';
import 'common.dart';

class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    return state.signedIn ? const _MyDonations() : const _SignInForm();
  }
}

class _MyDonations extends StatefulWidget {
  const _MyDonations();

  @override
  State<_MyDonations> createState() => _MyDonationsState();
}

class _MyDonationsState extends State<_MyDonations> {
  Future<List<Donation>>? _future;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= AppScope.of(context).api.myDonations();
  }

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    return Column(children: [
      ListTile(
        leading: const CircleAvatar(child: Icon(Icons.person)),
        title: Text(state.user!.name),
        subtitle: Text(state.user!.email),
        trailing: TextButton(onPressed: state.signOut, child: const Text('Sign out')),
      ),
      const Divider(),
      Expanded(
        child: AsyncView<List<Donation>>(
          future: _future!,
          onRetry: () => setState(() => _future = state.api.myDonations()),
          builder: (context, donations) => donations.isEmpty
              ? const Center(child: Text('Your donations will appear here.'))
              : RefreshIndicator(
                  onRefresh: () async => setState(() => _future = state.api.myDonations()),
                  child: ListView(children: [
                    for (final d in donations)
                      ListTile(
                        title: Text('${d.currency} ${d.amount} · ${d.fundName ?? ''}'),
                        subtitle: Text([
                          d.masjidName,
                          if (d.createdAt != null) DateFormat.yMMMd().format(d.createdAt!.toLocal()),
                          d.receiptNo ?? 'Ref #${d.id}',
                        ].whereType<String>().join(' · ')),
                        trailing: Chip(label: Text(d.status)),
                      ),
                  ]),
                ),
        ),
      ),
    ]);
  }
}

class _SignInForm extends StatefulWidget {
  const _SignInForm();

  @override
  State<_SignInForm> createState() => _SignInFormState();
}

class _SignInFormState extends State<_SignInForm> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _register = false;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final state = AppScope.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final (token, user) = _register
          ? await state.api.register(_name.text.trim(), _email.text.trim(), _password.text, 'mobile')
          : await state.api.login(_email.text.trim(), _password.text, 'mobile');
      await state.signIn(token, user);
    } on ApiException catch (e) {
      setState(() => _error = e.fieldErrors.values.expand((v) => v).firstOrNull ?? e.message);
    } catch (_) {
      setState(() => _error = 'Could not reach the masjid server.');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return ListView(padding: const EdgeInsets.all(24), children: [
      Text(_register ? 'Create account' : 'Sign in', style: Theme.of(context).textTheme.headlineSmall),
      const SizedBox(height: 8),
      const Text('Sign in to keep a history of your donations. You can donate without an account.'),
      const SizedBox(height: 16),
      if (_register) ...[
        TextField(controller: _name, decoration: const InputDecoration(labelText: 'Name', border: OutlineInputBorder())),
        const SizedBox(height: 12),
      ],
      TextField(
        controller: _email,
        keyboardType: TextInputType.emailAddress,
        autofillHints: const [AutofillHints.email],
        decoration: const InputDecoration(labelText: 'Email', border: OutlineInputBorder()),
      ),
      const SizedBox(height: 12),
      TextField(
        controller: _password,
        obscureText: true,
        decoration: const InputDecoration(labelText: 'Password', border: OutlineInputBorder()),
      ),
      if (_error != null) Padding(
        padding: const EdgeInsets.only(top: 12),
        child: Text(_error!, style: TextStyle(color: Theme.of(context).colorScheme.error)),
      ),
      const SizedBox(height: 16),
      FilledButton(onPressed: _busy ? null : _submit, child: Text(_register ? 'Create account' : 'Sign in')),
      TextButton(
        onPressed: () => setState(() => _register = !_register),
        child: Text(_register ? 'I already have an account' : 'Create an account'),
      ),
    ]);
  }
}
