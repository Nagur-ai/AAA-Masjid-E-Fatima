<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\FundType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFundRequest;
use App\Models\Fund;
use App\Models\TransactionCategory;
use App\Services\Finance\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FundController extends Controller
{
    public function index(LedgerService $ledger): View
    {
        $masjid = $this->masjid();

        return view('admin.funds.index', [
            'funds' => $masjid->funds()->orderBy('name')->get(),
            'balances' => $ledger->balances($masjid->id),
            'categories' => $masjid->categories()->orderBy('type')->orderBy('name')->get(),
            'types' => FundType::cases(),
        ]);
    }

    public function store(StoreFundRequest $request): RedirectResponse
    {
        $this->masjid()->funds()->create($this->withFlags($request));

        return back()->with('status', 'Fund created.');
    }

    public function update(StoreFundRequest $request, Fund $fund): RedirectResponse
    {
        $this->ensureOwned($fund);
        $fund->update($this->withFlags($request));

        return back()->with('status', "{$fund->name} updated.");
    }

    /**
     * Only a fund with no donations or ledger entries can be deleted; otherwise deactivate it.
     */
    public function destroy(Fund $fund): RedirectResponse
    {
        $this->ensureOwned($fund);
        if ($fund->donations()->exists() || $fund->transactions()->exists()) {
            return back()->withErrors(['fund' => "{$fund->name} has donations or ledger entries, so it cannot be deleted. Untick Active to hide it instead."]);
        }
        $fund->delete();

        return back()->with('status', "{$fund->name} deleted.");
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $masjid = $this->masjid();
        $data = $request->validate([
            'type' => ['required', Rule::enum(TransactionType::class)],
            'name' => ['required', 'string', 'max:80',
                Rule::unique('transaction_categories')->where('masjid_id', $masjid->id)->where('type', $request->input('type'))],
        ]);
        $masjid->categories()->create($data);

        return back()->with('status', 'Category added.');
    }

    public function updateCategory(Request $request, TransactionCategory $category): RedirectResponse
    {
        $this->ensureOwned($category);
        if ($category->is_system) {
            return back()->withErrors(['category' => "{$category->name} is used for every donation and cannot be changed."]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80',
                Rule::unique('transaction_categories')->where('masjid_id', $category->masjid_id)
                    ->where('type', $category->type->value)->ignore($category->id)],
        ]);
        $category->update($data);

        return back()->with('status', 'Category renamed.');
    }

    /**
     * Only an unused, non-system category can be deleted.
     */
    public function destroyCategory(TransactionCategory $category): RedirectResponse
    {
        $this->ensureOwned($category);
        if ($category->is_system || $this->masjid()->transactions()->where('category_id', $category->id)->exists()) {
            return back()->withErrors(['category' => "{$category->name} is used by ledger entries, so it cannot be deleted."]);
        }
        $category->delete();

        return back()->with('status', 'Category deleted.');
    }

    /**
     * HTML checkboxes are absent when unticked.
     */
    private function withFlags(StoreFundRequest $request): array
    {
        return [
            ...$request->validated(),
            'is_restricted' => $request->boolean('is_restricted'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
