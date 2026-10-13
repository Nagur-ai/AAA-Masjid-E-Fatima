import 'package:flutter_test/flutter_test.dart';
import 'package:masjid_app/models.dart';

void main() {
  test('parses the daily prayer-times response from the API', () {
    final today = TodayResponse.fromJson({
      'method': 'University of Islamic Sciences, Karachi',
      'data': {
        'date': '2026-10-02',
        'weekday': 'Friday',
        'hijri': {'year': 1448, 'month': 4, 'day': 21, 'formatted': "21 Rabi' al-Thani 1448 AH"},
        'times': {'fajr': '04:54', 'dhuhr': '12:05', 'maghrib': '18:04'},
        'iqamah': {'fajr': '05:15', 'dhuhr': null},
        'jumuah': [
          {'label': 'Jumuah', 'khutbah': '13:15', 'iqamah': '13:45', 'language': null},
        ],
      },
      'next_prayer': {'prayer': 'dhuhr', 'at': '2026-10-02T12:05:00+05:30'},
    });

    expect(today.schedule.times['fajr'], '04:54');
    expect(today.schedule.iqamah['dhuhr'], isNull);
    expect(today.schedule.jumuah.single.iqamah, '13:45');
    // The masjid's own clock time, regardless of the phone's timezone.
    expect(today.next!.masjidTime, '12:05');
    expect(today.prayerAt('fajr'), DateTime.utc(2026, 10, 2, 4, 54));
    expect(today.prayerAt('isha'), isNull);
  });

  test('reads the UTC offset from ISO timestamps', () {
    expect(parseUtcOffset('2026-10-02T07:52:10+05:30'), const Duration(hours: 5, minutes: 30));
    expect(parseUtcOffset('2026-10-02T02:22:10-04:00'), const Duration(hours: -4));
    expect(parseUtcOffset('2026-10-02T02:22:10Z'), Duration.zero);
  });
}
