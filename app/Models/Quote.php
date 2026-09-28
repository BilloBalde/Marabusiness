<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'processing', 'validated', 'cancelled'];

    protected $fillable = [
        'client_id',
        'type',
        'source_product_id',
        'study_destination_id',
        'moq',
        'message',
        'attachments',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function sourceProduct(): BelongsTo
    {
        return $this->belongsTo(SourceProduct::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * There's no "support" account or a per-quote assignee — every quote
     * goes to whichever admin the site has. If more than one exists, the
     * first is as good a choice as any right now; splitting the workload
     * between several admins is a later, separate decision.
     */
    public static function adminRecipient(): ?User
    {
        return User::role('admin')->first();
    }

    /**
     * A message from the client goes to the admin, and vice versa — the
     * caller doesn't need to know which, only who's actually posting. Paths
     * are already-stored file paths (public_uploads disk), not uploads
     * themselves: the caller stores the file first, same as the quote's own
     * ->attachments column does.
     */
    public function postMessage(User $author, string $content, array $attachmentPaths = []): Message
    {
        $admin = static::adminRecipient();
        $isClient = $author->is($this->client);

        $message = $this->messages()->create([
            'sender_id' => $author->id,
            'receiver_id' => $isClient ? $admin?->id : $this->client_id,
            'content' => $content,
        ]);

        foreach ($attachmentPaths as $path) {
            $message->attachments()->create(['path' => $path]);
        }

        return $message;
    }
}
