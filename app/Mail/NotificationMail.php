<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;
    public User $user;
    public string $customSubject;
    public string $customContent;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Notification $notification,
        User $user,
        string $customSubject = null,
        string $customContent = null
    ) {
        $this->notification = $notification;
        $this->user = $user;
        $this->customSubject = $customSubject ?? $notification->title;
        $this->customContent = $customContent ?? $notification->content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: $this->customSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'notification' => $this->notification,
                'user' => $this->user,
                'customContent' => $this->customContent,
                'actionUrl' => $this->notification->action_url,
                'actionText' => $this->notification->action_text ?? 'Ətraflı bax',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
