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
          if (cal.timetableImageUrl != null) _TimetableImage(url: cal.timetableImageUrl!),
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

/// The masjid's printed timetable; tap to open it full screen with zoom.
class _TimetableImage extends StatelessWidget {
  const _TimetableImage({required this.url});

  final String url;

  @override
  Widget build(BuildContext context) => Card(
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: () => showDialog<void>(
            context: context,
            builder: (_) => Dialog.fullscreen(
              child: Stack(children: [
                InteractiveViewer(maxScale: 5, child: Center(child: Image.network(url))),
                Positioned(
                  top: 8,
                  right: 8,
                  child: IconButton.filledTonal(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ),
              ]),
            ),
          ),
          child: Image.network(
            url,
            fit: BoxFit.fitWidth,
            errorBuilder: (_, __, ___) => const SizedBox.shrink(),
          ),
        ),
      );
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

/// Colours for the duas (match the website).
const _prayerColors = {
  'sehri': Color(0xFF6D4BB3),
  'iftar': Color(0xFF0F6B4F),
};
const _fridayGreen = Color(0xFF1B8A3A);
const _otherRed = Color(0xFFC62828);
const _headBlue = Color(0xFF1F3A8A);

/// The Ramadan timetable like the masjid's printed one: No., date, day, sehri and iftar.
/// Fridays are green and other days red.
class _RamadanTable extends StatelessWidget {
  const _RamadanTable({required this.days, required this.todayStr});

  final List<RamadanDay> days;
  final String todayStr;

  Widget _cell(String text, {TextStyle? style}) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
        child: Text(text, textAlign: TextAlign.center, style: style),
      );

  Widget _head(String te, String en) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
        child: Column(children: [
          Text(te, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          Text(en, style: const TextStyle(color: Colors.white70, fontSize: 11)),
        ]),
      );

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final small = Theme.of(context).textTheme.bodySmall;
    final grid = BorderSide(color: scheme.outlineVariant);

    TableRow row(RamadanDay d) {
      final friday = d.weekday == 'Friday';
      final today = d.date == todayStr;
      final color = friday ? _fridayGreen : _otherRed;
      final base = TextStyle(color: color, fontWeight: friday ? FontWeight.bold : FontWeight.normal);
      final time = base.copyWith(fontWeight: FontWeight.bold, fontFeatures: const [FontFeature.tabularFigures()]);
      return TableRow(
        decoration: BoxDecoration(
          color: today
              ? Colors.amber.withAlpha(60)
              : friday
                  ? _fridayGreen.withAlpha(25)
                  : null,
        ),
        children: [
          _cell('${d.day}', style: base),
          _cell(DateFormat('d-M-yy').format(DateTime.parse(d.date)), style: base),
          _cell(d.weekdayTe ?? d.weekday, style: base),
          _cell(d.sehriEnds ?? '—', style: time),
          _cell(d.iftar ?? '—', style: time),
        ],
      );
    }

    return Column(children: [
      Container(
        decoration: BoxDecoration(border: Border.all(color: _headBlue, width: 2)),
        child: Table(
          columnWidths: const {0: FlexColumnWidth(.8), 1: FlexColumnWidth(1.4), 2: FlexColumnWidth(1.5)},
          defaultVerticalAlignment: TableCellVerticalAlignment.middle,
          border: TableBorder(horizontalInside: grid, verticalInside: grid),
          children: [
            TableRow(
              decoration: const BoxDecoration(color: _headBlue),
              children: [
                _head('క్ర.సం', 'No.'),
                _head('తారీఖ్', 'Date'),
                _head('రోజ్', 'Day'),
                _head('సహరీ', 'Sehri'),
                _head('ఇఫ్తార్', 'Iftar'),
              ],
            ),
            for (final d in days) row(d),
          ],
        ),
      ),
      const SizedBox(height: 6),
      Row(mainAxisAlignment: MainAxisAlignment.center, children: [
        Container(width: 12, height: 12, color: _fridayGreen),
        const SizedBox(width: 4),
        Text('శుక్రవారం (Jumuah)', style: small),
        const SizedBox(width: 12),
        Container(width: 12, height: 12, color: _otherRed),
        const SizedBox(width: 4),
        Text('Other days', style: small),
      ]),
    ]);
  }
}
