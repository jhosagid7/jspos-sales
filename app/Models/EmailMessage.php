<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'related_model_id',
        'related_model_type',
        'customer_id',
        'email_address',
        'subject',
        'message_body',
        'attachment_path',
        'status',
        'sent_at',
        'error_message',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function relatedModel()
    {
        return $this->morphTo('related_model');
    }
}
