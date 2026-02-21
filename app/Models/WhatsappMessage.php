<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'seller_id',
        'phone_number',
        'message_body',
        'attachment_path',
        'status',
        'error_message',
        'related_model_type',
        'related_model_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function relatedModel()
    {
        return $this->morphTo();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'related_model_id')
                    ->where('related_model_type', Sale::class);
    }
}
