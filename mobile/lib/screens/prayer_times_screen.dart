import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../app_state.dart';
import '../models.dart';
import 'common.dart';
import 'masjid_picker_screen.dart';

const _fard = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];

/// The next prayer as seen on the masjid's clock right now.
class _Upcoming {
  const _Upcoming(this.prayer, this.time, this.remaining);

  final String prayer;
  final String time;
  final Duration remaining;
}

class PrayerTimesScreen extends StatefulWidget {
  const PrayerTimesScreen({super.key});

  @override
  State<PrayerTimesScreen> createState() => _PrayerTimesScreenState();
}

class _PrayerTimesScreenState extends State<PrayerTimesScreen> {
  Future<(Masjid, TodayResponse)>? _future;
  Timer? _ticker;
  DateTime? _lastReload;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
    // Tick every second so the clock and countdown stay current.
    _ticker ??= Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _ticker?.cancel();
    super.dispose();
  }

  Future<(Masjid, TodayResponse)> _load() async {
    _lastReload = DateTime.now();
    final state = AppScope.of(context);
    final results = await Future.wait([state.api.masjid(state.masjidSlug), state.api.today(state.masjidSlug)]);
    return (results[0] as Masjid, results[1] as TodayResponse);
  }

  void _reload() => setState(() => _future = _load());

  /// Reload at most once a minute, e.g. when the masjid's date rolls over.
  void _reloadSoon() {
    if (_lastReload != null && DateTime.now().difference(_lastReload!) < const Duration(minutes: 1)) return;
    Future.microtask(_reload);
  }

  _Upcoming? _upcoming(TodayResponse today, DateTime masjidNow) {
    for (final key in _fard) {
      final at = today.prayerAt(key);
      if (at != null && at.isAfter(masjidNow)) {
        return _Upcoming(key, today.schedule.times[key]!, at.difference(masjidNow));
      }
    }
    // After isha the server already worked out tomorrow's fajr.
    final next = today.next;
    if (next != null && next.at.isAfter(DateTime.now())) {
      return _Upcoming(next.prayer, next.masjidTime, next.at.difference(DateTime.now()));
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    return AsyncView<(Masjid, TodayResponse)>(
      future: _future!,
      onRetry: _reload,
      builder: (context, data) {
        final (masjid, today) = data;
        final masjidNow = today.masjidNow();
        if (DateFormat('yyyy-MM-dd').format(masjidNow) != today.schedule.date) _reloadSoon();
        final upcoming = _upcoming(today, masjidNow);

        return RefreshIndicator(
          onRefresh: () async => _reload(),
          child: ListView(padding: const EdgeInsets.all(16), children: [
            _Header(masjid: masjid, today: today, masjidNow: masjidNow, upcoming: upcoming),
            const SizedBox(height: 16),
            ...prayerLabels.entries.map((e) => _PrayerRow(
                  label: e.key == 'dhuhr' && today.schedule.weekday == 'Friday' ? 'Jumuah' : e.value,
                  adhan: today.schedule.times[e.key],
                  iqamah: today.schedule.iqamah[e.key],
                  highlighted: upcoming?.prayer == e.key,
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
  const _Header({required this.masjid, required this.today, required this.masjidNow, required this.upcoming});

  final Masjid masjid;
  final TodayResponse today;
  final DateTime masjidNow;
  final _Upcoming? upcoming;

  static String _countdown(Duration d) {
    String two(int n) => n.toString().padLeft(2, '0');
    return '${d.inHours}h ${two(d.inMinutes.remainder(60))}m ${two(d.inSeconds.remainder(60))}s';
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final text = Theme.of(context).textTheme;
    final onPrimary = TextStyle(color: scheme.onPrimary);
    final updated = today.fetchedAt.toUtc().add(today.utcOffset);

    return Card(
      color: scheme.primary,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: DefaultTextStyle(
          style: onPrimary,
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
              // Left: masjid and dates.
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Flexible(child: Text(masjid.name, style: text.titleLarge?.merge(onPrimary))),
                    IconButton(
                      tooltip: 'Change masjid',
                      color: scheme.onPrimary,
                      visualDensity: VisualDensity.compact,
                      icon: const Icon(Icons.swap_horiz),
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MasjidPickerScreen())),
                    ),
                  ]),
                  Text(DateFormat('EEEE, d MMM y').format(masjidNow)),
                  Text(today.schedule.hijri.formatted),
                ]),
              ),
              const SizedBox(width: 12),
              // Right: live clock in the masjid's own timezone.
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text(
                  DateFormat('HH:mm:ss').format(masjidNow),
                  style: text.headlineMedium?.merge(onPrimary).copyWith(
                        fontWeight: FontWeight.bold,
                        fontFeatures: const [FontFeature.tabularFigures()],
                      ),
                ),
                Opacity(opacity: .8, child: Text('Local time', style: text.bodySmall?.merge(onPrimary))),
              ]),
            ]),
            if (upcoming != null) ...[
              const SizedBox(height: 14),
              Text(
                'Next: ${prayerLabels[upcoming!.prayer]} at ${upcoming!.time}',
                style: text.titleMedium?.merge(onPrimary),
              ),
              Text(
                'in ${_countdown(upcoming!.remaining)}',
                style: const TextStyle(fontFeatures: [FontFeature.tabularFigures()]),
              ),
            ],
            const SizedBox(height: 10),
            Opacity(
              opacity: .75,
              child: Text('Updated ${DateFormat('HH:mm:ss').format(updated)} · ${masjid.timezone} · pull down to refresh',
                  style: text.bodySmall?.merge(onPrimary)),
            ),
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
