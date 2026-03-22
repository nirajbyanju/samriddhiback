<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Auditable;

class UserDetail extends Model
{
    use HasFactory, SoftDeletes, Auditable;
    protected $table = 'user_detail';

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'bio',
        'profile_picture',
        'gender',
        'country',
        'state',
        'district',
        'local_bodies',
        'street_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
