<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestDocument extends Model
{
    protected $fillable = [
        'request_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
