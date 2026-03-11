<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Property;
use App\Models\Data\InquiryType;
use App\Models\Data\PropertyType;

class Inquery extends Model
{
    use HasFactory;
    protected $table = 'inquiries';
    protected $fillable = [
        'property_id',
        'inquiry_type_id',
        'property_type_id',
        'name',
        'email',
        'phone',
        'preferred_location',
        'min_price',
        'max_price',
        'message',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }


    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function inquiryFollowup()
    {
        return $this->hasMany(InqueryFollowup::class);
    }
}
