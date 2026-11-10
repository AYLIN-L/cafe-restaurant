<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** Message reçu, jamais ouvert par l'administration. */
    public const STATUS_UNREAD = 'unread';

    /** Message ouvert mais pas encore traité (répondu ou classé). */
    public const STATUS_READ = 'read';

    /** Message traité par l'administration : il sort de la file. */
    public const STATUS_PROCESSED = 'processed';

    /** Tous les statuts stockés, dans l'ordre du cycle de vie. */
    public const STATUSES = [
        self::STATUS_UNREAD,
        self::STATUS_READ,
        self::STATUS_PROCESSED,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];

    /**
     * Tout nouveau message provient du formulaire public : il démarre
     * toujours « non lu », sans exception.
     */
    protected $attributes = [
        'status' => self::STATUS_UNREAD,
    ];

    protected $table = 'contact_messages';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Auteur tel qu'affiché à l'administration : compte associé sinon nom saisi. */
    public function senderName(): string
    {
        return (string) ($this->user?->name ?? $this->name ?? 'Expéditeur inconnu');
    }

    /** Expéditeur inscrit au site ? */
    public function hasLinkedAccount(): bool
    {
        return $this->user !== null;
    }

    /** Le message attend-il encore un traitement ? */
    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    /** Le message a-t-il été traité ? */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /** Libellé français du statut. */
    public function statusLabel(): string
    {
        return self::statusLabelFor($this->status);
    }

    /** Libellé français d'un statut (utile pour les messages de retour). */
    public static function statusLabelFor(string $status): string
    {
        return match ($status) {
            self::STATUS_PROCESSED => 'Traité',
            self::STATUS_READ => 'Lu · à traiter',
            self::STATUS_UNREAD => 'Non lu',
            default => $status,
        };
    }

    /** Messages pas encore traités : non lus, ou lus mais sans suite.
     *  C'est la base du compteur de notification de l'administration. */
    public function scopeUnprocessed(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_PROCESSED);
    }

    /** Messages jamais ouverts. */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_UNREAD);
    }
}
