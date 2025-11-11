<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class ClaimMail extends Mailable
{
    use Queueable, SerializesModels;
    public $claim; // On passe l'utilisateur dans l'e-mail

    /**
     * Create a new message instance.
     */
    public function __construct($claim)
    {
        $this->claim = $claim;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@agristock.fraiszo.com', 'Cool AgriStock'),
            subject: __('locale.claim_mail'),
            cc: array_map(fn($email) => new Address($email), ['yamooon664@gmail.com', 'client@coollionenergies.com', 'support@coollionenergies.com'])
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'mails.claim');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
