<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Api\V1\Admin\DonationController as ApiDonationController;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordDonationRequest;
use App\Models\Donation;
use App\Services\Finance\DonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function __construct(private readonly DonationService $donations) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(DonationStatus::class)],
            'fund_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $masjid = $this->masjid();

        return view('admin.donations.index', [
            'donations' => ApiDonationController::filtered($masjid, $filters)->with('fund')->latest('id')->paginate(25)->withQueryString(),
            'funds' => $masjid->funds()->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.donations.create', [
            'funds' => $this->masjid()->funds()->where('is_active', true)->orderBy('name')->get(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function store(RecordDonationRequest $request): RedirectResponse
    {
        $donation = $this->donations->recordReceived($this->masjid(), $request->validated(), $request->user());

        return redirect()->route('admin.donations.receipt', $donation)
            ->with('status', "Donation recorded. Receipt {$donation->receipt_no}.");
    }

    public function receipt(Donation $donation): View
    {
        $this->ensureOwned($donation);
        abort_unless($donation->receipt_no, 404);

        return view('admin.donations.receipt', ['donation' => $donation->load('fund', 'recorder')]);
    }

    public function complete(Request $request, Donation $donation): RedirectResponse
    {
        $this->ensureOwned($donation);
        $data = $request->validate(['payment_reference' => ['nullable', 'string', 'max:100']]);
        $donation = $this->donations->complete($donation, $request->user(), paymentReference: $data['payment_reference'] ?? null);

        return back()->with('status', "Donation confirmed. Receipt {$donation->receipt_no}.");
    }

    public function cancel(Donation $donation): RedirectResponse
    {
        $this->ensureOwned($donation);
        $this->donations->cancel($donation);

        return back()->with('status', 'Pledge cancelled.');
    }

    public function refund(Request $request, Donation $donation): RedirectResponse
    {
        $this->ensureOwned($donation);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->donations->refund($donation, $request->user(), $data['reason']);

        return back()->with('status', 'Donation refunded and its ledger entry voided.');
    }
}
