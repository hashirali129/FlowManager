<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestTypeRequest extends FormRequest
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
            'name' => 'required|string|unique:request_types,name,' . $this->route('request_type'),
            'description' => 'nullable|string',
            'form_schema' => [
                'required', 
                'array',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        foreach (array_keys($value) as $key) {
                            if (!preg_match('/^[a-z][a-z0-9_]*$/i', $key)) {
                                $fail("The field name \"$key\" is invalid. Use letters, numbers and underscores, starting with a letter.");
                            }
                        }
                    }
                }
            ],
            'form_schema.*' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        $validator = \Illuminate\Support\Facades\Validator::make(
                            ['test' => 'value'],
                            ['test' => $value]
                        );
                        $validator->passes();
                    } catch (\Exception $e) {
                        $fail("The validation rule \"$value\" is invalid or contains syntax errors.");
                    }
                }
            ],
        ];
    }
}
