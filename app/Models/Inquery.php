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
        'from',
        'inquiry_type_id',
        'property_type_id',
        'name',
        'email',
        'phone',
        'location',
        'budget',
        'message',
        'description',
        'response_type_id',
        'reason',
      
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
