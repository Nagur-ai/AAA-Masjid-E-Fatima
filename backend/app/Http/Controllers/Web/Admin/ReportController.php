<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Finance\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', ['report' => $reports->summary($this->masjid(), $from, $to)]);
    }

    /**
     * Ledger entries in the range as CSV, for the accountant or annual general meeting.
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $masjid = $this->masjid();

        return response()->streamDownload(function () use ($masjid, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Type', 'Category', 'Fund', 'Amount', 'Method', 'Party', 'Description', 'Status', 'Void reason']);

            $masjid->transactions()->with('category', 'fund')
                ->whereDate('transacted_on', '>=', $from)->whereDate('transacted_on', '<=', $to)
                ->orderBy('transacted_on')->orderBy('id')
                ->lazy()
                ->each(fn ($t) => fputcsv($out, [
                    $t->reference_no, $t->transacted_on->toDateString(), $t->type->value, $t->category->name,
                    $t->fund?->name, $t->amount, $t->payment_method->value, $t->party_name, $t->description,
                    $t->status->value, $t->void_reason,
                ]));

            fclose($out);
        }, "ledger-{$masjid->slug}-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $today = CarbonImmutable::now($this->masjid()->timezone);

        return [
            $data['from'] ?? $today->startOfYear()->toDateString(),
            $data['to'] ?? $today->toDateString(),
        ];
    }
}
