<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'type',
    ];

    /**
     * The channel this message belongs to.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * The user who sent the message (nullable for system/AI messages).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
