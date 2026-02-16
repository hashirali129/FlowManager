<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = ['user_id', 'request_type_id', 'status', 'current_step_order', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    protected $appends = ['current_step'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestType()
    {
        return $this->belongsTo(RequestType::class, 'request_type_id');
    }

    public function approvals()
    {
        return $this->hasMany(RequestApproval::class);
    }

    public function documents()
    {
        return $this->hasMany(RequestDocument::class);
    }

    public function getCurrentStepAttribute()
    {
        return $this->currentStep();
    }

    public function currentStep()
    {
        // Helper to get the current workflow step definition based on order
        // This assumes the request type has a workflow
        if (!$this->current_step_order) return null;

        // Fix: Access via requestType relationship
        return $this->requestType->workflow->steps()
            ->where('step_order', $this->current_step_order)
            ->first();
    }
}
