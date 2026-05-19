<?php

namespace App\Models;

use App\Domain\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'attachment_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(TicketDecision::class);
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(TicketDecision::class)->latestOfMany();
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', TicketStatus::Pending);
    }

    public function scopeApprovedByLevel1($query)
    {
        return $query->where('status', TicketStatus::ApprovedL1);
    }

    public function scopeAwaitingDelivery($query)
    {
        return $query->where('status', TicketStatus::ApprovedL2);
    }
}
