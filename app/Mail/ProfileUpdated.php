<?php
// app/Mail/ProfileUpdated.php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProfileUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $changes;
    public $oldData;
    public $passwordChanged;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, array $changes, array $oldData, bool $passwordChanged)
    {
        $this->user = $user;
        $this->changes = $changes;
        $this->oldData = $oldData;
        $this->passwordChanged = $passwordChanged;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Votre profil a été mis à jour')
                    ->view('emails.profile-updated');
    }
}