# Masjid App — Flutter client

Android/iOS app for the Laravel API in `../backend`. Screens: today's prayer times with countdown and iqamah, the monthly timetable, the Ramadan sehri/iftar calendar, donations, and the donation history of a signed-in user.

## First run

Platform folders (`android/`, `ios/`) are not committed. Generate them once:

```bash
cd mobile
flutter create . --org org.yourmasjid --project-name masjid_app --platforms android,ios
flutter pub get
```

Start the backend (`php artisan serve` in `../backend`), then:

```bash
# Android emulator (10.0.2.2 is the host machine)
flutter run

# Physical device or production server
flutter run --dart-define=API_BASE_URL=https://masjid.example.org/api/v1 --dart-define=MASJID_SLUG=masjid-e-fatima
```

For plain-HTTP local development on Android, allow cleartext traffic in `android/app/src/main/AndroidManifest.xml` (`android:usesCleartextTraffic="true"` on `<application>`). Use HTTPS in production.

## Structure

| File | Purpose |
| --- | --- |
| `lib/config.dart` | API URL and default masjid (`--dart-define`) |
| `lib/api_client.dart` | All HTTP calls; maps Laravel 422 errors to field errors |
| `lib/models.dart` | Data classes matching the API resources |
| `lib/app_state.dart` | Selected masjid and auth token, persisted with `shared_preferences` |
| `lib/screens/` | UI |

Times come from the server already in the masjid's timezone, so they display the same as the masjid's printed timetable wherever the phone is.
