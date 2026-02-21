<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConversationMessage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'agent',
        'role',
        'content',
        'meta',
    ];

    protected $attributes = [
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '{}',
        'meta' => '{}',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => AsArrayObject::class,
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function thinking(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->meta['thinking'] ?? null,
        );
    }

    /**
     * @return Attribute<array<int, array{label: string, icon: string, status: string}>, never>
     */
    protected function toolCalls(): Attribute
    {
        return Attribute::make(
            get: fn (): array => isset($this->meta['tool_calls']) ? (array) $this->meta['tool_calls'] : [],
        );
    }

    /**
     * @return BelongsTo<AgentConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
