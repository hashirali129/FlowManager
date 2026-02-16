<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActionRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We will handle authorization in Controller via Policy
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string',
        ];
    }
}
