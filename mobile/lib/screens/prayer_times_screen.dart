import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../app_state.dart';
import '../models.dart';
import 'common.dart';
import 'masjid_picker_screen.dart';

class PrayerTimesScreen extends StatefulWidget {
  const PrayerTimesScreen({super.key});

  @override
  State<PrayerTimesScreen> createState() => _PrayerTimesScreenState();
}

class _PrayerTimesScreenState extends State<PrayerTimesScreen> {
  Future<(Masjid, TodayResponse)>? _future;
  Timer? _ticker;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
    // Refresh the countdown every 30 seconds.
    _ticker ??= Timer.periodic(const Duration(seconds: 30), (_) => setState(() {}));
  }

  @override
  void dispose() {
    _ticker?.cancel();
    super.dispose();
  }

  Future<(Masjid, TodayResponse)> _load() async {
    final state = AppScope.of(context);
    final results = await Future.wait([state.api.masjid(state.masjidSlug), state.api.today(state.masjidSlug)]);
    return (results[0] as Masjid, results[1] as TodayResponse);
  }

  void _reload() => setState(() => _future = _load());

  @override
  Widget build(BuildContext context) {
    return AsyncView<(Masjid, TodayResponse)>(
      future: _future!,
      onRetry: _reload,
      builder: (context, data) {
        final (masjid, today) = data;
        return RefreshIndicator(
          onRefresh: () async => _reload(),
          child: ListView(padding: const EdgeInsets.all(16), children: [
            _Header(masjid: masjid, today: today),
            const SizedBox(height: 16),
            ...prayerLabels.entries.map((e) => _PrayerRow(
                  label: e.key == 'dhuhr' && today.schedule.weekday == 'Friday' ? 'Jumuah' : e.value,
                  adhan: today.schedule.times[e.key],
                  iqamah: today.schedule.iqamah[e.key],
                  highlighted: today.next?.prayer == e.key,
                )),
            if (masjid.jumuah.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text('Jumuah', style: Theme.of(context).textTheme.titleMedium),
              for (final s in masjid.jumuah)
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(s.label + (s.language != null ? ' (${s.language})' : '')),
                  subtitle: Text('Khutbah ${s.khutbah} · Iqamah ${s.iqamah}'),
                ),
            ],
            const SizedBox(height: 8),
            OutlinedButton.icon(
              icon: const Icon(Icons.calendar_month),
              label: const Text('Monthly timetable'),
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(
                builder: (_) => _MonthScreen(slug: masjid.slug, title: masjid.name),
              )),
            ),
            const SizedBox(height: 8),
            Text('Calculated with ${today.method}.', style: Theme.of(context).textTheme.bodySmall),
          ]),
        );
      },
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.masjid, required this.today});

  final Masjid masjid;
  final TodayResponse today;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final next = today.next;
    final remaining = next?.at.difference(DateTime.now());

    return Card(
      color: scheme.primary,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: DefaultTextStyle(
          style: TextStyle(color: scheme.onPrimary),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Expanded(
                child: Text(masjid.name, style: Theme.of(context).textTheme.titleLarge?.copyWith(color: scheme.onPrimary)),
              ),
              IconButton(
                tooltip: 'Change masjid',
                color: scheme.onPrimary,
                icon: const Icon(Icons.swap_horiz),
                onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MasjidPickerScreen())),
              ),
            ]),
            Text('${today.schedule.weekday} · ${today.schedule.hijri.formatted}'),
            if (next != null) ...[
              const SizedBox(height: 16),
              Text('Next: ${prayerLabels[next.prayer]}'),
              Text(
                next.masjidTime,
                style: Theme.of(context).textTheme.displaySmall?.copyWith(color: scheme.onPrimary, fontWeight: FontWeight.bold),
              ),
              if (remaining != null && !remaining.isNegative)
                Text('in ${remaining.inHours}h ${remaining.inMinutes.remainder(60)}m'),
            ],
          ]),
        ),
      ),
    );
  }
}

class _PrayerRow extends StatelessWidget {
  const _PrayerRow({required this.label, this.adhan, this.iqamah, this.highlighted = false});

  final String label;
  final String? adhan;
  final String? iqamah;
  final bool highlighted;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      elevation: 0,
      color: highlighted ? scheme.primaryContainer : scheme.surfaceContainerHighest,
      child: ListTile(
        title: Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: iqamah != null ? Text('Iqamah $iqamah') : null,
        trailing: Text(adhan ?? '—', style: Theme.of(context).textTheme.titleLarge),
      ),
    );
  }
}

class _MonthScreen extends StatefulWidget {
  const _MonthScreen({required this.slug, required this.title});

  final String slug;
  final String title;

  @override
  State<_MonthScreen> createState() => _MonthScreenState();
}

class _MonthScreenState extends State<_MonthScreen> {
  DateTime _month = DateTime(DateTime.now().year, DateTime.now().month);
  Future<List<DailySchedule>>? _future;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
  }

  Future<List<DailySchedule>> _load() => AppScope.of(context).api.month(widget.slug, _month.year, _month.month);

  void _shift(int months) => setState(() {
        _month = DateTime(_month.year, _month.month + months);
        _future = _load();
      });

  @override
  Widget build(BuildContext context) {
    final todayStr = DateFormat('yyyy-MM-dd').format(DateTime.now());
    return Scaffold(
      appBar: AppBar(
        title: Text(DateFormat.yMMMM().format(_month)),
        actions: [
          IconButton(icon: const Icon(Icons.chevron_left), onPressed: () => _shift(-1)),
          IconButton(icon: const Icon(Icons.chevron_right), onPressed: () => _shift(1)),
        ],
      ),
      body: AsyncView<List<DailySchedule>>(
        future: _future!,
        onRetry: () => setState(() => _future = _load()),
        builder: (context, days) => SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: SingleChildScrollView(
            child: DataTable(
              columnSpacing: 18,
              columns: [
                const DataColumn(label: Text('Date')),
                for (final l in prayerLabels.values) DataColumn(label: Text(l)),
              ],
              rows: [
                for (final d in days)
                  DataRow(
                    selected: d.date == todayStr,
                    cells: [
                      DataCell(Text('${d.weekday.substring(0, 3)} ${int.parse(d.date.substring(8))}')),
                      for (final k in prayerLabels.keys) DataCell(Text(d.times[k] ?? '—')),
                    ],
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
