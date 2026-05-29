<?php

namespace App\Models;

use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'is_private',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }

    /**
     * The workspace this channel belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The user who created the channel.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Messages in this channel.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Members of the channel (CHAN-04/06).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_user')->withTimestamps();
    }
}
