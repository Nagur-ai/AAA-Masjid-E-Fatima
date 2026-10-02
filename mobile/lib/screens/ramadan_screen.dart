import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../app_state.dart';
import '../models.dart';
import 'common.dart';

class RamadanScreen extends StatefulWidget {
  const RamadanScreen({super.key});

  @override
  State<RamadanScreen> createState() => _RamadanScreenState();
}

class _RamadanScreenState extends State<RamadanScreen> {
  int? _year;
  Future<RamadanCalendar>? _future;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
  }

  Future<RamadanCalendar> _load() async {
    final state = AppScope.of(context);
    final calendar = await state.api.ramadan(state.masjidSlug, hijriYear: _year);
    _year = calendar.hijriYear;
    return calendar;
  }

  void _shift(int years) => setState(() {
        _year = _year! + years;
        _future = _load();
      });

  String _date(String iso, String pattern) => DateFormat(pattern).format(DateTime.parse(iso));

  @override
  Widget build(BuildContext context) {
    final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());
    return AsyncView<RamadanCalendar>(
      future: _future!,
      onRetry: () => setState(() => _future = _load()),
      builder: (context, cal) {
        final today = cal.days.where((d) => d.date == todayStr).firstOrNull;
        return ListView(padding: const EdgeInsets.all(16), children: [
          Row(children: [
            IconButton(icon: const Icon(Icons.chevron_left), onPressed: () => _shift(-1)),
            Expanded(
              child: Column(children: [
                Text('Ramadan ${cal.hijriYear} AH', style: Theme.of(context).textTheme.titleLarge),
                Text('${_date(cal.startsOn, 'd MMM')} – ${_date(cal.endsOn, 'd MMM y')}'),
              ]),
            ),
            IconButton(icon: const Icon(Icons.chevron_right), onPressed: () => _shift(1)),
          ]),
          const SizedBox(height: 8),
          if (!cal.isConfirmed)
            const Card(
              child: ListTile(
                leading: Icon(Icons.info_outline),
                title: Text('Estimated dates'),
                subtitle: Text('The masjid will confirm the start after the moon sighting.'),
              ),
            ),
          if (today != null)
            Card(
              color: Theme.of(context).colorScheme.primaryContainer,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
                  _BigTime(label: 'Sehri ends', time: today.sehriEnds),
                  _BigTime(label: 'Iftar', time: today.iftar),
                ]),
              ),
            ),
          if (cal.taraweehTime != null || cal.notes != null)
            Card(
              child: ListTile(
                title: Text(cal.taraweehTime != null ? 'Taraweeh at ${cal.taraweehTime}' : 'Announcements'),
                subtitle: cal.notes != null ? Text(cal.notes!) : null,
              ),
            ),
          const SizedBox(height: 8),
          for (final d in cal.days)
            ListTile(
              selected: d.date == todayStr,
              leading: CircleAvatar(child: Text('${d.day}')),
              title: Text('${d.weekday}, ${_date(d.date, 'd MMM')}'),
              subtitle: Text('Sehri ${d.sehriEnds} · Iftar ${d.iftar}'
                  '${d.oddNightAfterIftar != null ? ' · Night ${d.oddNightAfterIftar}' : ''}'),
              trailing: d.isLastTen ? const Icon(Icons.star_outline) : null,
            ),
          ListTile(
            leading: const Icon(Icons.celebration),
            title: const Text('Eid al-Fitr (expected)'),
            subtitle: Text(_date(cal.eidAlFitr, 'EEEE d MMMM y')),
          ),
        ]);
      },
    );
  }
}

class _BigTime extends StatelessWidget {
  const _BigTime({required this.label, this.time});

  final String label;
  final String? time;

  @override
  Widget build(BuildContext context) => Column(children: [
        Text(label),
        Text(time ?? '—', style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold)),
      ]);
}
