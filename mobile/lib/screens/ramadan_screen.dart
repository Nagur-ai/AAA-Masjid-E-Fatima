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
          if (cal.duas.isNotEmpty)
            Card(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Text('సహరీ & ఇఫ్తార్ దుఆలు', style: Theme.of(context).textTheme.titleMedium),
                  for (final dua in cal.duas) _DuaView(dua: dua),
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
          _RamadanTable(days: cal.days, todayStr: todayStr),
          const SizedBox(height: 8),
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

class _DuaView extends StatelessWidget {
  const _DuaView({required this.dua});

  final Dua dua;

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).textTheme;
    final color = _prayerColors[dua.key] ?? Theme.of(context).colorScheme.primary;
    return Container(
      margin: const EdgeInsets.only(top: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withAlpha(15),
        border: Border(top: BorderSide(color: color, width: 4)),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text(dua.titleTe, style: text.titleSmall?.copyWith(color: color, fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        Text(dua.telugu, style: text.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
      ]),
    );
  }
}

/// Border colour for each prayer column (matches the website).
const _prayerColors = {
  'sehri': Color(0xFF6D4BB3),
  'fajr': Color(0xFF2F6FB5),
  'dhuhr': Color(0xFFB8862B),
  'asr': Color(0xFFC26A1B),
  'iftar': Color(0xFF0F6B4F),
  'isha': Color(0xFF3B4A8F),
};
const _fridayColor = Color(0xFFD9A521);

/// The Ramadan timetable as a table: each prayer in its own bordered column, Fridays highlighted.
class _RamadanTable extends StatelessWidget {
  const _RamadanTable({required this.days, required this.todayStr});

  final List<RamadanDay> days;
  final String todayStr;

  static const _columns = [
    ('sehri', 'Sehri', 'సహరీ'),
    ('fajr', 'Fajr', 'ఫజర్'),
    ('dhuhr', 'Dhuhr', 'జుహర్'),
    ('asr', 'Asr', 'అసర్'),
    ('iftar', 'Iftar', 'ఇఫ్తార్'),
    ('isha', 'Isha', 'ఇషా'),
  ];

  String? _time(RamadanDay d, String key) => switch (key) {
        'sehri' => d.sehriEnds,
        'iftar' => d.iftar,
        _ => d.times[key],
      };

  Widget _cell(Widget child, {Color? border, Color? fill, bool top = false, bool bottom = false}) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: fill,
          border: border == null
              ? null
              : Border(
                  left: BorderSide(color: border, width: 2),
                  right: BorderSide(color: border, width: 2),
                  top: top ? BorderSide(color: border, width: 4) : BorderSide.none,
                  bottom: bottom ? BorderSide(color: border, width: 2) : BorderSide.none,
                ),
        ),
        child: child,
      );

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final small = Theme.of(context).textTheme.bodySmall;
    final grid = BorderSide(color: scheme.outlineVariant);

    TableRow header() => TableRow(children: [
          _cell(const Text('Roza', style: TextStyle(fontWeight: FontWeight.bold))),
          _cell(const Text('Date / వారం', style: TextStyle(fontWeight: FontWeight.bold))),
          for (final (key, en, te) in _columns)
            _cell(
              Column(children: [
                Text(en, style: TextStyle(fontWeight: FontWeight.bold, color: _prayerColors[key])),
                Text(te, style: small?.copyWith(color: _prayerColors[key])),
              ]),
              border: _prayerColors[key],
              fill: _prayerColors[key]!.withAlpha(41),
              top: true,
            ),
        ]);

    TableRow row(RamadanDay d, bool last) {
      final friday = d.weekday == 'Friday';
      final today = d.date == todayStr;
      final rowFill = today
          ? scheme.primaryContainer
          : friday
              ? _fridayColor.withAlpha(56)
              : null;
      final weight = friday || today ? FontWeight.bold : FontWeight.normal;
      return TableRow(
        decoration: BoxDecoration(color: rowFill),
        children: [
          _cell(Text('${d.day}', style: const TextStyle(fontWeight: FontWeight.bold))),
          _cell(Column(children: [
            Text(DateFormat('EEE d MMM').format(DateTime.parse(d.date)), style: TextStyle(fontWeight: weight)),
            if (d.weekdayTe != null) Text(d.weekdayTe!, style: small?.copyWith(fontWeight: weight)),
            if (friday)
              Container(
                margin: const EdgeInsets.only(top: 2),
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(color: _fridayColor, borderRadius: BorderRadius.circular(8)),
                child: const Text('Jumuah',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.black)),
              ),
            if (d.oddNightAfterIftar != null) Text('Night ${d.oddNightAfterIftar}', style: small),
          ])),
          for (final (key, _, _) in _columns)
            _cell(
              Text(_time(d, key) ?? '—',
                  style: TextStyle(
                    fontWeight: key == 'sehri' || key == 'iftar' || friday ? FontWeight.bold : FontWeight.normal,
                    fontFeatures: const [FontFeature.tabularFigures()],
                  )),
              border: _prayerColors[key],
              fill: rowFill == null ? _prayerColors[key]!.withAlpha(18) : null,
              bottom: last,
            ),
        ],
      );
    }

    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Table(
          defaultColumnWidth: const IntrinsicColumnWidth(),
          defaultVerticalAlignment: TableCellVerticalAlignment.middle,
          border: TableBorder(horizontalInside: grid, top: grid, bottom: grid, left: grid, right: grid),
          children: [
            header(),
            for (var i = 0; i < days.length; i++) row(days[i], i == days.length - 1),
          ],
        ),
      ),
      const SizedBox(height: 6),
      Row(children: [
        Container(width: 12, height: 12, color: _fridayColor.withAlpha(128)),
        const SizedBox(width: 4),
        Text('Friday (Jumuah)', style: small),
        const SizedBox(width: 12),
        Container(width: 12, height: 12, color: scheme.primaryContainer),
        const SizedBox(width: 4),
        Text('Today', style: small),
      ]),
    ]);
  }
}
