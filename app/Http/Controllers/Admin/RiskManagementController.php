<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Risk\RiskRule;
use App\Models\Risk\RiskThreshold;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class RiskManagementController extends Controller
{
    public function dashboard(): View
    {
        $page_title = __('Risk Management');

        $summary = [
            'pending' => Transaction::query()->where('risk_decision', 'pending')->count(),
            'manual_review' => Transaction::query()->where('risk_decision', 'manual_review')->count(),
            'rejected' => Transaction::query()->where('risk_decision', 'reject')->count(),
            'approved' => Transaction::query()->where('risk_decision', 'approve')->count(),
        ];

        $recentIncidents = Transaction::query()
            ->whereIn('risk_decision', ['manual_review', 'reject'])
            ->latest()
            ->with([
                'user:id,firstname,lastname,email',
                'merchant:id,firstname,lastname,business_name,email',
                'agent:id,firstname,lastname,email',
            ])
            ->paginate(15);

        $rules = RiskRule::query()->orderBy('priority')->get();
        $thresholds = RiskThreshold::query()->orderBy('priority')->get();

        return view('admin.sections.risk.dashboard', compact(
            'page_title',
            'summary',
            'recentIncidents',
            'rules',
            'thresholds'
        ));
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $conditions = $this->decodeConditions($request->input('conditions'));

        if ($conditions === null) {
            return back()->withErrors(['conditions' => [__('Unable to parse rule conditions. Please provide valid JSON.')]])
                ->withInput()
                ->with('modal', 'riskRuleCreate');
        }

        $data = array_merge($request->all(), [
            'conditions' => $conditions,
        ]);

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'match_type' => ['required', Rule::in(['all', 'any'])],
            'action' => ['required', Rule::in(['approve', 'manual_review', 'reject'])],
            'priority' => ['required', 'integer', 'min:0'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.field' => ['required', 'string'],
            'conditions.*.operator' => ['required', 'string'],
            'conditions.*.value' => ['nullable'],
            'description' => ['nullable', 'string'],
            'stop_on_match' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'riskRuleCreate');
        }

        $validated = $validator->validated();
        $validated['stop_on_match'] = $request->boolean('stop_on_match');
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $payload = Arr::only($validated, [
                'name',
                'event_type',
                'match_type',
                'action',
                'priority',
                'conditions',
                'description',
                'stop_on_match',
                'is_active',
            ]);

            RiskRule::create($payload);
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]])->withInput()->with('modal', 'riskRuleCreate');
        }

        return back()->with(['success' => [__('Rule created successfully.')]]);
    }

    public function updateRule(Request $request, RiskRule $rule): RedirectResponse
    {
        $conditions = $this->decodeConditions($request->input('conditions'));

        if ($conditions === null) {
            return back()->withErrors(['conditions' => [__('Unable to parse rule conditions. Please provide valid JSON.')]])
                ->withInput()
                ->with('modal', 'riskRuleEdit');
        }

        $data = array_merge($request->all(), [
            'conditions' => $conditions,
        ]);

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'match_type' => ['required', Rule::in(['all', 'any'])],
            'action' => ['required', Rule::in(['approve', 'manual_review', 'reject'])],
            'priority' => ['required', 'integer', 'min:0'],
            'conditions' => ['required', 'array', 'min:1'],
            'conditions.*.field' => ['required', 'string'],
            'conditions.*.operator' => ['required', 'string'],
            'conditions.*.value' => ['nullable'],
            'description' => ['nullable', 'string'],
            'stop_on_match' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'riskRuleEdit');
        }

        $validated = $validator->validated();
        $validated['stop_on_match'] = $request->boolean('stop_on_match');
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $payload = Arr::only($validated, [
                'name',
                'event_type',
                'match_type',
                'action',
                'priority',
                'conditions',
                'description',
                'stop_on_match',
                'is_active',
            ]);

            $rule->update($payload);
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]])->withInput()->with('modal', 'riskRuleEdit');
        }

        return back()->with(['success' => [__('Rule updated successfully.')]]);
    }

    public function deleteRule(RiskRule $rule): RedirectResponse
    {
        try {
            $rule->delete();
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]]);
        }

        return back()->with(['success' => [__('Rule deleted successfully.')]]);
    }

    public function storeThreshold(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => ['required', 'string', 'max:255'],
            'comparator' => ['required', Rule::in(['gte', 'gt', 'lte', 'lt', 'eq', 'neq'])],
            'value' => ['required', 'numeric'],
            'decision' => ['required', Rule::in(['approve', 'manual_review', 'reject'])],
            'priority' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'thresholdCreate');
        }

        $validated = $validator->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            RiskThreshold::create($validated);
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]])->withInput()->with('modal', 'thresholdCreate');
        }

        return back()->with(['success' => [__('Threshold created successfully.')]]);
    }

    public function updateThreshold(Request $request, RiskThreshold $threshold): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => ['required', 'string', 'max:255'],
            'comparator' => ['required', Rule::in(['gte', 'gt', 'lte', 'lt', 'eq', 'neq'])],
            'value' => ['required', 'numeric'],
            'decision' => ['required', Rule::in(['approve', 'manual_review', 'reject'])],
            'priority' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal', 'thresholdEdit');
        }

        $validated = $validator->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        try {
            $threshold->update($validated);
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]])->withInput()->with('modal', 'thresholdEdit');
        }

        return back()->with(['success' => [__('Threshold updated successfully.')]]);
    }

    public function deleteThreshold(RiskThreshold $threshold): RedirectResponse
    {
        try {
            $threshold->delete();
        } catch (Throwable $exception) {
            return back()->withErrors(['error' => [$exception->getMessage()]]);
        }

        return back()->with(['success' => [__('Threshold deleted successfully.')]]);
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    protected function decodeConditions(?string $conditions): ?array
    {
        if (blank($conditions)) {
            return null;
        }

        $decoded = json_decode($conditions, true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_values(array_filter($decoded, static fn ($condition) => is_array($condition)));
    }
}
