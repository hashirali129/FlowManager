<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type_id' => 'required|exists:request_types,id',
            'payload' => ['required', 'array', function ($attribute, $value, $fail) {
                // ... dynamic validation logic ...
            }],
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:15360', // 15MB limit
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requestTypeId = $this->input('request_type_id');
            if (!$requestTypeId) return;

            $requestType = \App\Models\RequestType::find($requestTypeId);
            if (!$requestType || empty($requestType->form_schema)) {
                return;
            }

            // Apply dynamic rules from form_schema to the payload
            // Rules in form_schema are like: ['amount' => 'required|numeric']
            // We need to validate 'payload.amount'

            $rules = [];
            $attributes = [];
            foreach ($requestType->form_schema as $field => $rule) {
                $rules["payload.$field"] = $rule;
                $attributes["payload.$field"] = $field;
            }

            $payloadValidator = \Illuminate\Support\Facades\Validator::make(
                $this->all(),
                $rules,
                [],
                $attributes
            );

            if ($payloadValidator->fails()) {
                foreach ($payloadValidator->errors()->all() as $error) {
                    $validator->errors()->add('payload', $error);
                }
            }
        });
    }
}
