<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ListArticlesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('has_analysis')) {
            $this->merge([
                'has_analysis' => filter_var($this->query('has_analysis'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'source_id' => ['sometimes', 'integer', 'exists:sources,id'],
            'has_analysis' => ['sometimes', 'boolean'],
            'published_from' => ['sometimes', 'date'],
            'published_to' => ['sometimes', 'date', 'after_or_equal:published_from'],
            'q' => ['sometimes', 'string', 'max:200'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
