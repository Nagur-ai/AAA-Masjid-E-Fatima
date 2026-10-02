import 'dart:convert';

import 'package:http/http.dart' as http;

import 'config.dart';
import 'models.dart';

class ApiException implements Exception {
  final int status;
  final String message;
  final Map<String, List<String>> fieldErrors;

  ApiException(this.status, this.message, [this.fieldErrors = const {}]);

  @override
  String toString() => message;
}

/// Thin wrapper over the Laravel `/api/v1` endpoints.
class ApiClient {
  ApiClient({http.Client? client, this.baseUrl = apiBaseUrl}) : _http = client ?? http.Client();

  final http.Client _http;
  final String baseUrl;
  String? token;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<dynamic> _get(String path, [Map<String, String>? query]) async {
    final uri = Uri.parse('$baseUrl$path').replace(queryParameters: query);
    return _decode(await _http.get(uri, headers: _headers));
  }

  Future<dynamic> _post(String path, Map<String, dynamic> body) async {
    return _decode(await _http.post(Uri.parse('$baseUrl$path'), headers: _headers, body: jsonEncode(body)));
  }

  dynamic _decode(http.Response res) {
    final body = res.body.isEmpty ? null : jsonDecode(res.body);
    if (res.statusCode >= 200 && res.statusCode < 300) return body;

    final errors = <String, List<String>>{};
    if (body is Map && body['errors'] is Map) {
      (body['errors'] as Map).forEach((k, v) => errors[k as String] = List<String>.from(v));
    }
    final message = body is Map && body['message'] is String ? body['message'] as String : 'Request failed (${res.statusCode})';
    throw ApiException(res.statusCode, message, errors);
  }

  // Masjids and prayer times

  Future<List<Masjid>> masjids({String? query, double? lat, double? lng}) async {
    final json = await _get('/masjids', {
      if (query != null && query.isNotEmpty) 'q': query,
      if (lat != null) 'lat': '$lat',
      if (lng != null) 'lng': '$lng',
    });
    return (json['data'] as List).map((e) => Masjid.fromJson(e)).toList();
  }

  Future<Masjid> masjid(String slug) async => Masjid.fromJson((await _get('/masjids/$slug'))['data']);

  Future<TodayResponse> today(String slug, {String? date}) async =>
      TodayResponse.fromJson(await _get('/masjids/$slug/prayer-times', {if (date != null) 'date': date}));

  Future<List<DailySchedule>> month(String slug, int year, int month) async {
    final json = await _get('/masjids/$slug/prayer-times/monthly', {'year': '$year', 'month': '$month'});
    return (json['data'] as List).map((e) => DailySchedule.fromJson(e)).toList();
  }

  Future<RamadanCalendar> ramadan(String slug, {int? hijriYear}) async {
    final path = hijriYear == null ? '/masjids/$slug/ramadan' : '/masjids/$slug/ramadan/$hijriYear';
    return RamadanCalendar.fromJson((await _get(path))['data']);
  }

  // Donations

  Future<List<Fund>> funds(String slug) async =>
      ((await _get('/masjids/$slug/funds'))['data'] as List).map((e) => Fund.fromJson(e)).toList();

  /// Returns the pending donation and the masjid's payment instructions.
  Future<(Donation, String?)> pledge(String slug, Map<String, dynamic> body) async {
    final json = await _post('/masjids/$slug/donations', body);
    return (Donation.fromJson(json['data']), json['payment_instructions'] as String?);
  }

  Future<List<Donation>> myDonations() async =>
      ((await _get('/me/donations'))['data'] as List).map((e) => Donation.fromJson(e)).toList();

  // Auth

  Future<(String, AppUser)> login(String email, String password, String deviceName) async {
    final json = await _post('/auth/login', {'email': email, 'password': password, 'device_name': deviceName});
    return (json['token'] as String, AppUser.fromJson(json['user']));
  }

  Future<(String, AppUser)> register(String name, String email, String password, String deviceName) async {
    final json = await _post('/auth/register', {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'device_name': deviceName,
    });
    return (json['token'] as String, AppUser.fromJson(json['user']));
  }

  Future<AppUser> me() async => AppUser.fromJson((await _get('/auth/me'))['data']);

  Future<void> logout() async {
    await _post('/auth/logout', {});
  }
}
