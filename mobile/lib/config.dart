/// API base URL. Override at build time:
///   flutter run --dart-define=API_BASE_URL=https://masjid.example.org/api/v1
///
/// The default points the Android emulator at `php artisan serve` on the host.
const String apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000/api/v1',
);

/// Masjid opened on first launch, before the user picks one.
const String defaultMasjidSlug = String.fromEnvironment('MASJID_SLUG', defaultValue: 'klr');
