<?php

namespace khdija\SupportChat\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $table = 'support_messages';

    protected $fillable = [
        'business_id',
        'sender_role',
        'sender_id',
        'context_user_id',
        'body',
        'read_by_admin_at',
        'read_by_business_at',
    ];

    protected $casts = [
        'read_by_admin_at'    => 'datetime',
        'read_by_business_at' => 'datetime',
    ];
}
