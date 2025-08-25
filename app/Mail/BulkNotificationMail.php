<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Kütləvi notification göndərmək üçün
 */
class BulkNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public $subject;
    public string $content;
    public ?string $actionUrl;
    public ?string $actionText;

    public function __construct(
        User $user,
        string $subject,
        string $content,
        ?string $actionUrl = null,
        ?string $actionText = null
    ) {
        $this->user = $user;
        $this->subject = $subject;
        $this->content = $content;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText ?? 'Ətraflı bax';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bulk-notification',
            with: [
                'user' => $this->user,
                'customContent' => $this->content,
                'actionUrl' => $this->actionUrl,
                'actionText' => $this->actionText,
                'subject' => $this->subject,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
