import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';
import 'config.dart';
import 'models.dart';

/// App-wide state: the chosen masjid and the signed-in user. Persisted locally.
class AppState extends ChangeNotifier {
  AppState(this.api);

  final ApiClient api;
  late SharedPreferences _prefs;

  String masjidSlug = defaultMasjidSlug;
  AppUser? user;

  bool get signedIn => user != null;

  Future<void> load() async {
    _prefs = await SharedPreferences.getInstance();
    masjidSlug = _prefs.getString('masjid') ?? defaultMasjidSlug;
    api.token = _prefs.getString('token');
    if (api.token != null) {
      try {
        user = await api.me();
      } on ApiException catch (e) {
        if (e.status == 401) await _clearSession();
      } catch (_) {
        // Offline: keep the token and try again next launch.
      }
    }
    notifyListeners();
  }

  Future<void> selectMasjid(String slug) async {
    masjidSlug = slug;
    await _prefs.setString('masjid', slug);
    notifyListeners();
  }

  Future<void> signIn(String token, AppUser signedIn) async {
    api.token = token;
    user = signedIn;
    await _prefs.setString('token', token);
    notifyListeners();
  }

  Future<void> signOut() async {
    try {
      await api.logout();
    } catch (_) {}
    await _clearSession();
    notifyListeners();
  }

  Future<void> _clearSession() async {
    api.token = null;
    user = null;
    await _prefs.remove('token');
  }
}

/// Makes [AppState] available to the widget tree.
class AppScope extends InheritedNotifier<AppState> {
  const AppScope({super.key, required AppState state, required super.child}) : super(notifier: state);

  static AppState of(BuildContext context) => context.dependOnInheritedWidgetOfExactType<AppScope>()!.notifier!;
}
