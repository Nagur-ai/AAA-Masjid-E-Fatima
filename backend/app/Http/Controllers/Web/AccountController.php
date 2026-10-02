<?php

namespace App\Http\Controllers\Web;

use App\Enums\DonationStatus;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Masjid;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A member's own area: their donations and receipts, nothing else.
 */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $donations = $request->user()->donations();

        $totals = (clone $donations)
            ->where('status', DonationStatus::Completed)
            ->groupBy('currency')
            ->selectRaw('currency, SUM(amount) as total, COUNT(*) as count')
            ->toBase()->get();

        return view('account.index', [
            'donations' => (clone $donations)->with('fund', 'masjid')->latest('id')->paginate(15),
            'totals' => $totals,
            'pendingCount' => (clone $donations)->where('status', DonationStatus::Pending)->count(),
            // Donate again where they last gave, or the first masjid.
            'donateTo' => (clone $donations)->latest('id')->first()?->masjid ?? Masjid::orderBy('id')->first(),
        ]);
    }

    public function receipt(Request $request, Donation $donation): View
    {
        abort_unless((int) $donation->user_id === $request->user()->id && $donation->receipt_no, 404);

        return view('account.receipt', ['donation' => $donation->load('fund', 'masjid', 'recorder')]);
    }
}
