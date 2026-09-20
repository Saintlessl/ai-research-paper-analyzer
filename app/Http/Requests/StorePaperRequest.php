<?php

namespace App\Http\Requests;

use App\Models\Paper;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StorePaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Paper::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['nullable', 'string', 'max:50000'],
            'publication_year' => ['nullable', 'integer', 'min:1600', 'max:'.(now()->year + 1)],
            'journal' => ['nullable', 'string', 'max:255'],
            'doi' => ['nullable', 'string', 'max:255', Rule::unique(Paper::class, 'doi')],
            'keywords' => ['nullable', 'array', 'list', 'max:20'],
            'keywords.*' => ['required', 'string', 'max:100'],
            'authors' => ['nullable', 'array', 'list', 'max:100'],
            'authors.*' => ['array:name,email,affiliation,orcid'],
            'authors.*.name' => ['required', 'string', 'max:255'],
            'authors.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'authors.*.affiliation' => ['nullable', 'string', 'max:255'],
            'authors.*.orcid' => ['nullable', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'file' => [
                'required',
                File::types(['pdf'])->max((int) config('papers.max_upload_kilobytes')),
                'extensions:pdf',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value instanceof UploadedFile
                        && mb_strlen($value->getClientOriginalName()) > 255) {
                        $fail('The PDF filename may not be greater than 255 characters.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->trimmed('title'),
            'abstract' => $this->trimmedOrNull('abstract'),
            'journal' => $this->trimmedOrNull('journal'),
            'doi' => $this->normalizedDoi(),
            'keywords' => $this->normalizedKeywords(),
            'authors' => $this->normalizedAuthors(),
        ]);
    }

    private function trimmed(string $key): mixed
    {
        $value = $this->input($key);

        return is_string($value) ? trim($value) : $value;
    }

    private function trimmedOrNull(string $key): mixed
    {
        $value = $this->trimmed($key);

        return $value === '' ? null : $value;
    }

    private function normalizedDoi(): mixed
    {
        $doi = $this->trimmedOrNull('doi');

        return is_string($doi) ? mb_strtolower($doi) : $doi;
    }

    private function normalizedKeywords(): mixed
    {
        $keywords = $this->input('keywords');

        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        if (! is_array($keywords)) {
            return $keywords;
        }

        if (! array_is_list($keywords)) {
            return $keywords;
        }

        $normalized = [];
        $seen = [];

        foreach ($keywords as $keyword) {
            if (! is_string($keyword)) {
                $normalized[] = $keyword;

                continue;
            }

            $keyword = trim($keyword);

            if ($keyword === '') {
                continue;
            }

            $key = mb_strtolower($keyword);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $normalized[] = $keyword;
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizedAuthors(): mixed
    {
        $authors = $this->input('authors');

        if (! is_array($authors)) {
            return $authors;
        }

        if (! array_is_list($authors)) {
            return $authors;
        }

        return array_map(function (mixed $author): mixed {
            if (! is_array($author)) {
                return $author;
            }

            foreach (['name', 'email', 'affiliation', 'orcid'] as $field) {
                if (array_key_exists($field, $author) && is_string($author[$field])) {
                    $value = trim($author[$field]);
                    $author[$field] = $value === '' && $field !== 'name' ? null : $value;
                }
            }

            return $author;
        }, $authors);
    }
}
