<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowRequest extends FormRequest
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
            'name' => 'required|string',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.role_id' => 'required|exists:roles,id',
            'steps.*.step_order' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $steps = $this->input('steps');
            if (empty($steps)) return;

            $orders = array_column($steps, 'step_order');
            sort($orders, SORT_NUMERIC);

            // 1. Check if it starts with 1
            if ($orders[0] != 1) {
                $validator->errors()->add('steps', 'Workflow steps must start with step_order 1.');
                return;
            }

            // 2. Check for sequence gaps
            for ($i = 0; $i < count($orders); $i++) {
                if ($orders[$i] != $i + 1) {
                    $validator->errors()->add('steps', 'Workflow steps must be sequential (1, 2, 3...) without gaps.');
                    return;
                }
            }
        });
    }
}
