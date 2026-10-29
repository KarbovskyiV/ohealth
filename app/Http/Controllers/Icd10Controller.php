<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Icd10;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Icd10Controller extends Controller
{
    /**
     * Search active ICD-10 codes the encounter writer employee is allowed to use.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'max:255']
        ]);

        $employee = Auth::user()->getEncounterWriterEmployee();

        $query = Icd10::search($validated['search'])->active()->limit(50);

        $allowedCodes = $employee?->allowedConditionCodesBySystem()['eHealth/ICD10_AM/condition_codes'] ?? null;
        if ($allowedCodes !== null) {
            $query->whereIn('code', $allowedCodes);
        }

        $forbiddenCodes = $employee?->forbiddenIcd10ConditionCodes() ?? [];
        if ($forbiddenCodes !== []) {
            $query->whereNotIn('code', $forbiddenCodes);
        }

        return response()->json($query->get(['code', 'description']));
    }

    /**
     * Search ICD-10 codes for list filters, inactive ones included on request.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'max:255'],
            'with_inactive' => ['sometimes', 'boolean']
        ]);

        $codes = Icd10::search($validated['search'])
            ->unless($request->boolean('with_inactive'), static fn (Builder $query): Builder => $query->active())
            ->limit(50)
            ->get(['code', 'description']);

        return response()->json($codes);
    }

    /**
     * Get the descriptions of the given ICD-10 codes keyed by code. Inactive codes are included, as the stored
     * records may still use them.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function descriptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codes' => ['required', 'array'],
            'codes.*' => ['required', 'string', 'max:255']
        ]);

        return response()->json(Icd10::whereIn('code', $validated['codes'])->pluck('description', 'code'));
    }
}
