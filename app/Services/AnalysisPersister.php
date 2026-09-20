<?php

namespace App\Services;

use App\Models\Paper;
use App\Models\PaperAnalysis;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AnalysisPersister
{
    private const CRITERIA = [
        'clarity', 'methodological_rigor', 'novelty', 'validity',
        'reproducibility', 'significance', 'evidence_quality',
    ];

    public function persist(Paper $paper, array $payload): PaperAnalysis
    {
        $data = Validator::make($payload, $this->rules())->validate();
        Validator::make(
            ['criteria' => array_column($data['scores'], 'criterion')],
            ['criteria' => ['required', 'array', 'size:7'], 'criteria.*' => ['distinct', Rule::in(self::CRITERIA)]],
        )->validate();

        return DB::transaction(function () use ($paper, $data): PaperAnalysis {
            $old = $paper->analysis()->first();
            if ($old) {
                $old->delete();
            }
            $methodology = $data['methodology'];
            $analysis = $paper->analysis()->create([
                'paper_type' => $data['classification']['paper_type'],
                'research_domain' => $data['classification']['research_domain'],
                'summary' => $data['structure']['summary'],
                'research_problem' => $methodology['research_problem'],
                'research_questions' => $methodology['research_questions'],
                'research_objective' => $methodology['research_objective'],
                'hypothesis' => $methodology['hypothesis'],
                'structure' => $data['structure'],
                'methodology' => $methodology,
                'dataset' => $methodology['dataset'],
                'sample_size' => $methodology['sample_size'],
                'key_findings' => $data['findings'],
                'limitations' => $data['limitations'],
                'strengths' => $data['strengths'],
                'weaknesses' => $data['weaknesses'],
                'keywords' => $data['keywords'],
                'raw_output' => $data,
            ]);
            $analysis->scores()->createMany($data['scores']);
            $analysis->findings()->createMany(array_merge(
                $data['findings'], $data['limitations'], $data['strengths'], $data['weaknesses'],
            ));

            return $analysis->load(['scores', 'findings']);
        });
    }

    private function rules(): array
    {
        $nullableString = ['nullable', 'string'];
        $evidence = [
            '*.evidence' => ['present', 'array'],
            '*.evidence.*.page' => ['nullable', 'integer', 'min:1'],
            '*.evidence.*.section' => $nullableString,
            '*.evidence.*.chunk_id' => $nullableString,
            '*.evidence.*.excerpt' => ['nullable', 'string', 'max:1000'],
            '*.evidence.*.confidence' => ['nullable', 'numeric', 'between:0,1'],
        ];
        $rules = [
            'classification' => ['required', 'array'],
            'classification.paper_type' => $nullableString,
            'classification.research_domain' => $nullableString,
            'classification.reason' => $nullableString,
            'classification.evidence' => ['present', 'array'],
            'structure' => ['required', 'array'],
            'structure.summary' => $nullableString,
            'structure.sections' => ['present', 'array'],
            'structure.evidence' => ['present', 'array'],
            'methodology' => ['required', 'array'],
            'methodology.research_problem' => $nullableString,
            'methodology.research_questions' => ['nullable', 'array'],
            'methodology.research_questions.*' => ['string'],
            'methodology.research_objective' => $nullableString,
            'methodology.hypothesis' => $nullableString,
            'methodology.study_design' => $nullableString,
            'methodology.methods' => ['nullable', 'array'],
            'methodology.methods.*' => ['string'],
            'methodology.dataset' => $nullableString,
            'methodology.sample_size' => ['nullable', 'integer', 'min:0'],
            'methodology.evidence' => ['present', 'array'],
            'scores' => ['required', 'array', 'size:7'],
            'scores.*.criterion' => ['required', Rule::in(self::CRITERIA)],
            'scores.*.score' => ['required', 'integer', 'between:0,100'],
            'scores.*.reason' => ['required', 'string', 'min:1'],
            'scores.*.evidence' => ['present', 'array'],
            'findings' => ['present', 'array'],
            'limitations' => ['present', 'array'],
            'strengths' => ['present', 'array'],
            'weaknesses' => ['present', 'array'],
            'keywords' => ['present', 'array'],
            'keywords.*' => ['string'],
        ];
        foreach (['findings' => 'finding', 'limitations' => 'limitation', 'strengths' => 'strength', 'weaknesses' => 'weakness'] as $field => $kind) {
            $rules["$field.*.kind"] = ['required', Rule::in([$kind])];
            $rules["$field.*.severity"] = ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])];
            $rules["$field.*.category"] = ['required', 'string'];
            $rules["$field.*.finding"] = ['required', 'string'];
            $rules["$field.*.explanation"] = $nullableString;
            $rules["$field.*.confidence"] = ['nullable', 'numeric', 'between:0,1'];
            foreach ($evidence as $suffix => $rule) {
                $rules["$field.$suffix"] = $rule;
            }
        }
        foreach (['classification', 'structure', 'methodology', 'scores.*'] as $field) {
            $rules["$field.evidence.*.page"] = ['nullable', 'integer', 'min:1'];
            $rules["$field.evidence.*.section"] = $nullableString;
            $rules["$field.evidence.*.chunk_id"] = $nullableString;
            $rules["$field.evidence.*.excerpt"] = ['nullable', 'string', 'max:1000'];
            $rules["$field.evidence.*.confidence"] = ['nullable', 'numeric', 'between:0,1'];
        }
        return $rules;
    }
}
