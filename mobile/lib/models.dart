/// Plain data classes mirroring the Laravel API resources.
library;

class Masjid {
  final String slug;
  final String name;
  final String? address;
  final String? city;
  final String timezone;
  final String currency;
  final String method;
  final String? bankDetails;
  final double? distanceKm;
  final List<JumuahSession> jumuah;

  Masjid.fromJson(Map<String, dynamic> j)
      : slug = j['slug'],
        name = j['name'],
        address = j['address'],
        city = j['city'],
        timezone = j['timezone'],
        currency = j['currency'],
        method = j['calculation_method']?['name'] ?? '',
        bankDetails = j['bank_details'],
        distanceKm = (j['distance_km'] as num?)?.toDouble(),
        jumuah = ((j['jumuah'] as List?) ?? []).map((e) => JumuahSession.fromJson(e)).toList();
}

class JumuahSession {
  final String label;
  final String khutbah;
  final String iqamah;
  final String? language;

  JumuahSession.fromJson(Map<String, dynamic> j)
      : label = j['label'],
        khutbah = j['khutbah'],
        iqamah = j['iqamah'],
        language = j['language'];
}

class HijriDate {
  final int year;
  final int month;
  final int day;
  final String formatted;

  HijriDate.fromJson(Map<String, dynamic> j)
      : year = j['year'],
        month = j['month'],
        day = j['day'],
        formatted = j['formatted'];
}

/// Times are "HH:mm" strings in the masjid's local time, exactly as published.
class DailySchedule {
  final String date;
  final String weekday;
  final HijriDate hijri;
  final Map<String, String?> times;
  final Map<String, String?> iqamah;
  final List<JumuahSession> jumuah;

  DailySchedule.fromJson(Map<String, dynamic> j)
      : date = j['date'],
        weekday = j['weekday'],
        hijri = HijriDate.fromJson(j['hijri']),
        times = Map<String, String?>.from(j['times']),
        iqamah = Map<String, String?>.from(j['iqamah']),
        jumuah = ((j['jumuah'] as List?) ?? []).map((e) => JumuahSession.fromJson(e)).toList();
}

class NextPrayer {
  final String prayer;

  /// Absolute instant, for countdowns.
  final DateTime at;

  /// "HH:mm" in the masjid's timezone (the phone may be elsewhere).
  final String masjidTime;

  NextPrayer.fromJson(Map<String, dynamic> j)
      : prayer = j['prayer'],
        at = DateTime.parse(j['at']),
        masjidTime = (j['at'] as String).substring(11, 16);
}

class TodayResponse {
  final DailySchedule schedule;
  final NextPrayer? next;
  final String method;

  TodayResponse.fromJson(Map<String, dynamic> j)
      : schedule = DailySchedule.fromJson(j['data']),
        next = j['next_prayer'] == null ? null : NextPrayer.fromJson(j['next_prayer']),
        method = j['method'];
}

class RamadanDay {
  final int day;
  final String date;
  final String weekday;
  final String? sehriEnds;
  final String? imsak;
  final String? iftar;
  final Map<String, String?> times;
  final bool isLastTen;
  final int? oddNightAfterIftar;

  RamadanDay.fromJson(Map<String, dynamic> j)
      : day = j['day'],
        date = j['date'],
        weekday = j['weekday'],
        sehriEnds = j['sehri_ends'],
        imsak = j['imsak'],
        iftar = j['iftar'],
        times = Map<String, String?>.from(j['times']),
        isLastTen = j['is_last_ten'],
        oddNightAfterIftar = j['odd_night_after_iftar'];
}

class RamadanCalendar {
  final int hijriYear;
  final String startsOn;
  final String endsOn;
  final String eidAlFitr;
  final bool isConfirmed;
  final String? taraweehTime;
  final String? notes;
  final List<RamadanDay> days;

  RamadanCalendar.fromJson(Map<String, dynamic> j)
      : hijriYear = j['hijri_year'],
        startsOn = j['starts_on'],
        endsOn = j['ends_on'],
        eidAlFitr = j['eid_al_fitr'],
        isConfirmed = j['is_confirmed'],
        taraweehTime = j['taraweeh_time'],
        notes = j['notes'],
        days = (j['days'] as List).map((e) => RamadanDay.fromJson(e)).toList();
}

class Fund {
  final int id;
  final String name;
  final String typeLabel;
  final String? description;

  Fund.fromJson(Map<String, dynamic> j)
      : id = j['id'],
        name = j['name'],
        typeLabel = j['type_label'],
        description = j['description'];
}

class Donation {
  final int id;
  final String? receiptNo;
  final String status;
  final String amount;
  final String currency;
  final String paymentMethod;
  final String? fundName;
  final String? masjidName;
  final DateTime? createdAt;

  Donation.fromJson(Map<String, dynamic> j)
      : id = j['id'],
        receiptNo = j['receipt_no'],
        status = j['status'],
        amount = j['amount'],
        currency = j['currency'],
        paymentMethod = j['payment_method'],
        fundName = j['fund']?['name'],
        masjidName = j['masjid']?['name'],
        createdAt = j['created_at'] == null ? null : DateTime.parse(j['created_at']);
}

class AppUser {
  final int id;
  final String name;
  final String email;
  final String role;

  AppUser.fromJson(Map<String, dynamic> j)
      : id = j['id'],
        name = j['name'],
        email = j['email'],
        role = j['role'];
}
