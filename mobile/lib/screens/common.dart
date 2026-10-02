import 'package:flutter/material.dart';

import '../api_client.dart';

/// Loads [future] and renders loading, error-with-retry, or [builder].
class AsyncView<T> extends StatelessWidget {
  const AsyncView({super.key, required this.future, required this.builder, required this.onRetry});

  final Future<T> future;
  final Widget Function(BuildContext, T) builder;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: future,
      builder: (context, snap) {
        if (snap.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snap.hasError) {
          final message = snap.error is ApiException ? snap.error.toString() : 'Could not reach the masjid server.';
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                const Icon(Icons.cloud_off, size: 48),
                const SizedBox(height: 12),
                Text(message, textAlign: TextAlign.center),
                const SizedBox(height: 12),
                FilledButton.tonal(onPressed: onRetry, child: const Text('Try again')),
              ]),
            ),
          );
        }
        return builder(context, snap.data as T);
      },
    );
  }
}

const prayerLabels = {
  'fajr': 'Fajr',
  'sunrise': 'Sunrise',
  'dhuhr': 'Dhuhr',
  'asr': 'Asr',
  'maghrib': 'Maghrib',
  'isha': 'Isha',
};
