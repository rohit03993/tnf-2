<?php

namespace App\Http\Requests;

use App\Support\SubmissionContent;
use App\Support\TnfImageUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Submission::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'embed_url' => ['nullable', 'url', 'max:500'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'image' => TnfImageUpload::validationRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $content = (string) $this->input('content', '');

            if (SubmissionContent::textLength($content) < 50) {
                $validator->errors()->add('content', 'Please write at least 50 characters of story content.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'image.max' => TnfImageUpload::validationMessage(),
        ];
    }
}
