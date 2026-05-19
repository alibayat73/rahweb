<?php

namespace App\Models;

use App\Domain\Enums\Decision;
use Database\Factories\TicketDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDecision extends Model
{
    /** @use HasFactory<TicketDecisionFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'admin_id',
        'decision',
        'note',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'decision' => Decision::class,
            'level' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
