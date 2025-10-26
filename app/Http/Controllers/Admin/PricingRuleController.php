<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pricing\PricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PricingRuleController extends Controller
{
    public function index(Request $request): View
    {
        $query = PricingRule::query()->withCount('feeTiers');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('currency', 'like', "%{$search}%")
                    ->orWhere('transaction_type', 'like', "%{$search}%");
            });
        }

        $rules = $query->orderBy('priority')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.sections.pricing-rules.index', compact('rules'));
    }

    public function create(): View
    {
        $rule = new PricingRule([
            'priority' => 100,
            'fee_type' => 'percentage',
            'fee_currency' => get_default_currency_code(),
            'active' => true,
            'variant' => 'control',
        ]);

        return view('admin.sections.pricing-rules.create', compact('rule'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRule($request);

        DB::transaction(function () use ($validated) {
            $tiers = $validated['tiers'] ?? [];
            unset($validated['tiers']);

            /** @var PricingRule $rule */
            $rule = PricingRule::create($validated);
            $rule->syncFeeTiers($tiers ?? []);
        });

        return redirect()->route('admin.pricing-rules.index')->with('success', [__('Pricing rule created successfully.')]);
    }

    public function edit(PricingRule $pricingRule): View
    {
        $pricingRule->load('feeTiers');

        return view('admin.sections.pricing-rules.edit', [
            'rule' => $pricingRule,
        ]);
    }

    public function update(Request $request, PricingRule $pricingRule): RedirectResponse
    {
        $validated = $this->validateRule($request);

        DB::transaction(function () use ($pricingRule, $validated) {
            $tiers = $validated['tiers'] ?? [];
            unset($validated['tiers']);

            $pricingRule->update($validated);
            $pricingRule->syncFeeTiers($tiers ?? []);
        });

        return redirect()->route('admin.pricing-rules.index')->with('success', [__('Pricing rule updated successfully.')]);
    }

    public function destroy(PricingRule $pricingRule): RedirectResponse
    {
        $pricingRule->delete();

        return back()->with('success', [__('Pricing rule removed successfully.')]);
    }

    protected function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'currency' => ['required', 'string', 'max:12'],
            'provider' => ['required', 'string', 'max:190'],
            'transaction_type' => ['required', 'string', 'max:120'],
            'user_level' => ['nullable', 'string', 'max:120'],
            'fee_type' => ['required', 'string', 'max:50'],
            'fee_amount' => ['required', 'numeric', 'min:0'],
            'fee_currency' => ['nullable', 'string', 'max:12'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'experiment' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
            'metadata' => ['nullable', 'array'],
            'metadata.description' => ['nullable', 'string', 'max:255'],
            'metadata.notes' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'tiers' => ['nullable', 'array'],
            'tiers.*.id' => ['nullable', 'integer'],
            'tiers.*.min_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.fee_type' => ['nullable', 'string', 'max:50'],
            'tiers.*.fee_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.fee_currency' => ['nullable', 'string', 'max:12'],
            'tiers.*.priority' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['fee_currency'] = $validated['fee_currency'] ?? $validated['currency'];

        return $validated;
    }
}
