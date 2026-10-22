<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    /** Avis en attente de modération. */
    public const MODERATION_PENDING = 'pending';

    /** Avis approuvé : il est visible publiquement. */
    public const MODERATION_APPROVED = 'approved';

    /** Avis rejeté : il n'est jamais visible publiquement. */
    public const MODERATION_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'menu_id',
        'content',
        'rating',
        'is_approved',
        'moderated_at',
        'moderated_by',
        'moderation_note',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    protected $table = 'comments';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    /** Administrateur ayant modéré l'avis. */
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * État de modération de l'avis.
     *
     * `is_approved` reste la seule source de vérité de la visibilité
     * publique : un avis rejeté n'est jamais affiché publiquement.
     */
    public function moderationState(): string
    {
        if ($this->is_approved) {
            return self::MODERATION_APPROVED;
        }

        return $this->moderated_at !== null
            ? self::MODERATION_REJECTED
            : self::MODERATION_PENDING;
    }

    /** L'avis a-t-il été approuvé ? */
    public function isApproved(): bool
    {
        return (bool) $this->is_approved;
    }

    /** L'avis attend-il encore une modération ? */
    public function isPendingModeration(): bool
    {
        return $this->moderationState() === self::MODERATION_PENDING;
    }

    /** L'avis a-t-il été rejeté ? */
    public function isRejected(): bool
    {
        return $this->moderationState() === self::MODERATION_REJECTED;
    }

    /** Libellé lisible de l'état de modération. */
    public function moderationLabel(): string
    {
        return match ($this->moderationState()) {
            self::MODERATION_APPROVED => 'Approuvé',
            self::MODERATION_REJECTED => 'Rejeté',
            default => 'En attente',
        };
    }

    /** Avis visibles publiquement. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /** Avis en attente de modération. */
    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->where('is_approved', false)->whereNull('moderated_at');
    }

    /** Avis rejetés. */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('is_approved', false)->whereNotNull('moderated_at');
    }
}
