import 'package:flutter/material.dart';

import '../app_state.dart';
import '../models.dart';
import 'common.dart';

class MasjidPickerScreen extends StatefulWidget {
  const MasjidPickerScreen({super.key});

  @override
  State<MasjidPickerScreen> createState() => _MasjidPickerScreenState();
}

class _MasjidPickerScreenState extends State<MasjidPickerScreen> {
  final _search = TextEditingController();
  Future<List<Masjid>>? _future;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _future ??= _load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<List<Masjid>> _load() => AppScope.of(context).api.masjids(query: _search.text.trim());

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    return Scaffold(
      appBar: AppBar(title: const Text('Choose masjid')),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.all(16),
          child: TextField(
            controller: _search,
            decoration: const InputDecoration(prefixIcon: Icon(Icons.search), hintText: 'Name or city', border: OutlineInputBorder()),
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => setState(() => _future = _load()),
          ),
        ),
        Expanded(
          child: AsyncView<List<Masjid>>(
            future: _future!,
            onRetry: () => setState(() => _future = _load()),
            builder: (context, masjids) => masjids.isEmpty
                ? const Center(child: Text('No masjids found.'))
                : ListView(children: [
                    for (final m in masjids)
                      ListTile(
                        leading: const Icon(Icons.mosque),
                        title: Text(m.name),
                        subtitle: Text([m.city, if (m.distanceKm != null) '${m.distanceKm!.toStringAsFixed(1)} km'].whereType<String>().join(' · ')),
                        trailing: m.slug == state.masjidSlug ? const Icon(Icons.check) : null,
                        onTap: () async {
                          await state.selectMasjid(m.slug);
                          if (context.mounted) Navigator.of(context).pop();
                        },
                      ),
                  ]),
          ),
        ),
      ]),
    );
  }
}
