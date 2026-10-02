import 'package:flutter/material.dart';

import 'api_client.dart';
import 'app_state.dart';
import 'screens/account_screen.dart';
import 'screens/donate_screen.dart';
import 'screens/prayer_times_screen.dart';
import 'screens/ramadan_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final state = AppState(ApiClient());
  await state.load();
  runApp(AppScope(state: state, child: const MasjidApp()));
}

class MasjidApp extends StatelessWidget {
  const MasjidApp({super.key});

  @override
  Widget build(BuildContext context) {
    const seed = Color(0xFF0F6B4F);
    return MaterialApp(
      title: 'Masjid',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(colorSchemeSeed: seed, useMaterial3: true),
      darkTheme: ThemeData(colorSchemeSeed: seed, brightness: Brightness.dark, useMaterial3: true),
      home: const HomeShell(),
    );
  }
}

class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    // Rebuild every tab with a fresh key when the masjid changes.
    final slug = AppScope.of(context).masjidSlug;
    final pages = [
      PrayerTimesScreen(key: ValueKey('times-$slug')),
      RamadanScreen(key: ValueKey('ramadan-$slug')),
      DonateScreen(key: ValueKey('donate-$slug')),
      const AccountScreen(),
    ];

    return Scaffold(
      body: SafeArea(child: IndexedStack(index: _tab, children: pages)),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.access_time), label: 'Prayer'),
          NavigationDestination(icon: Icon(Icons.nightlight_round), label: 'Ramadan'),
          NavigationDestination(icon: Icon(Icons.volunteer_activism), label: 'Donate'),
          NavigationDestination(icon: Icon(Icons.person), label: 'Account'),
        ],
      ),
    );
  }
}
