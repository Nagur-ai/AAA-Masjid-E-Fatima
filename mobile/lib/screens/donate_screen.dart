import 'package:flutter/material.dart';

import '../api_client.dart';
import '../app_state.dart';
import '../models.dart';
import 'common.dart';

class DonateScreen extends StatefulWidget {
  const DonateScreen({super.key});

  @override
  State<DonateScreen> createState() => _DonateScreenState();
}

class _DonateScreenState extends State<DonateScreen> {
  static const _methods = {'upi': 'UPI', 'bank_transfer': 'Bank transfer', 'card': 'Card', 'cheque': 'Cheque'};

  final _form = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _reference = TextEditingController();
  Future<(Masjid, List<Fund>)>? _future;
  int? _fundId;
  String _method = 'upi';
  bool _anonymous = false;
  bool _submitting = false;
  Map<String, List<String>> _errors = {};

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
  }

  @override
  void dispose() {
    for (final c in [_amount, _name, _phone, _reference]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<(Masjid, List<Fund>)> _load() async {
    final state = AppScope.of(context);
    final results = await Future.wait([state.api.masjid(state.masjidSlug), state.api.funds(state.masjidSlug)]);
    return (results[0] as Masjid, results[1] as List<Fund>);
  }

  Future<void> _submit(Masjid masjid) async {
    if (!_form.currentState!.validate()) return;
    final state = AppScope.of(context);
    setState(() {
      _submitting = true;
      _errors = {};
    });

    try {
      final (donation, instructions) = await state.api.pledge(masjid.slug, {
        'fund_id': _fundId,
        'amount': _amount.text.trim(),
        'payment_method': _method,
        if (_reference.text.trim().isNotEmpty) 'payment_reference': _reference.text.trim(),
        if (_name.text.trim().isNotEmpty) 'donor_name': _name.text.trim(),
        if (_phone.text.trim().isNotEmpty) 'donor_phone': _phone.text.trim(),
        'is_anonymous': _anonymous,
      });
      if (!mounted) return;
      _amount.clear();
      _reference.clear();
      await showDialog<void>(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('JazakAllahu khayran'),
          content: Text('Your pledge of ${donation.currency} ${donation.amount} is recorded as reference #${donation.id}. '
              'The treasurer will issue a receipt when the payment is received.'
              '${instructions != null ? '\n\nPay to:\n$instructions\n\nQuote reference #${donation.id}.' : ''}'),
          actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('OK'))],
        ),
      );
    } on ApiException catch (e) {
      setState(() => _errors = e.fieldErrors);
      if (e.fieldErrors.isEmpty && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not reach the masjid server.')));
      }
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  String? _serverError(String field) => _errors[field]?.first;

  @override
  Widget build(BuildContext context) {
    final signedIn = AppScope.of(context).signedIn;
    return AsyncView<(Masjid, List<Fund>)>(
      future: _future!,
      onRetry: () => setState(() => _future = _load()),
      builder: (context, data) {
        final (masjid, funds) = data;
        _fundId ??= funds.isNotEmpty ? funds.first.id : null;
        return Form(
          key: _form,
          child: ListView(padding: const EdgeInsets.all(16), children: [
            Text('Donate to ${masjid.name}', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 16),
            DropdownButtonFormField<int>(
              initialValue: _fundId,
              decoration: InputDecoration(labelText: 'Fund', errorText: _serverError('fund_id'), border: const OutlineInputBorder()),
              items: [for (final f in funds) DropdownMenuItem(value: f.id, child: Text(f.name))],
              onChanged: (v) => setState(() => _fundId = v),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _amount,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(
                labelText: 'Amount (${masjid.currency})',
                errorText: _serverError('amount'),
                border: const OutlineInputBorder(),
              ),
              validator: (v) => (double.tryParse(v ?? '') ?? 0) >= 1 ? null : 'Enter an amount of at least 1',
            ),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              initialValue: _method,
              decoration: const InputDecoration(labelText: 'Paying by', border: OutlineInputBorder()),
              items: [for (final e in _methods.entries) DropdownMenuItem(value: e.key, child: Text(e.value))],
              onChanged: (v) => setState(() => _method = v!),
            ),
            const SizedBox(height: 12),
            if (!signedIn) ...[
              TextFormField(
                controller: _name,
                decoration: InputDecoration(labelText: 'Your name', errorText: _serverError('donor_name'), border: const OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(labelText: 'Phone', errorText: _serverError('donor_phone'), border: const OutlineInputBorder()),
              ),
              const SizedBox(height: 12),
            ],
            TextFormField(
              controller: _reference,
              decoration: const InputDecoration(labelText: 'Transaction reference (if already paid)', border: OutlineInputBorder()),
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Keep my name private'),
              value: _anonymous,
              onChanged: (v) => setState(() => _anonymous = v),
            ),
            FilledButton(
              onPressed: _submitting ? null : () => _submit(masjid),
              child: Text(_submitting ? 'Sending…' : 'Pledge donation'),
            ),
            if (masjid.bankDetails != null) ...[
              const SizedBox(height: 24),
              Text('Payment details', style: Theme.of(context).textTheme.titleMedium),
              SelectableText(masjid.bankDetails!),
            ],
          ]),
        );
      },
    );
  }
}
