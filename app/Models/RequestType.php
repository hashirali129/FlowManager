<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestType extends Model
{
    protected $fillable = ['name', 'description', 'form_schema'];

    protected $casts = [
        'form_schema' => 'array',
    ];

    public function workflow()
    {
        return $this->hasOne(Workflow::class);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }
}
