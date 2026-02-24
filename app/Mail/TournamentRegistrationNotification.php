<?php

namespace App\Mail;

use App\Models\TournamentRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TournamentRegistrationNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public TournamentRegistration $record,
        public string $action = 'Nueva Inscripcion'
        ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notificacion de inscripcion: '. $this->action,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tournament-registration',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        // Si existe el archivo, se adjunta al mail
        if ($this->record->payment_file && Storage::disk('public')->exists($this->record->payment_file)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->record->payment_file)
                ->as('comprobante-pago.png')
                ->withMime('image/png');
        }
        
        return $attachments;
    }
}
