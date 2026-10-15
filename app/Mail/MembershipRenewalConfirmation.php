<?php

namespace App\Mail;

use App\Helpers\FabPath;
use App\Models\Membership;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MembershipRenewalConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    private string $pdfData;

    public function __construct(
        public Membership $membership,
        public Collection $renewals,
        public User $generatedBy,
    ) {
        $this->membership->loadMissing('discipline');
        $this->renewals->each->loadMissing(['player', 'club']);

        $this->pdfData = Pdf::loadView('pdf.membership-renewal', $this->viewData())
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante de membresías '.$this->membership->year.' - '.$this->membership->discipline?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-renewal',
            with: $this->viewData(),
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => $this->pdfData,
                'comprobante-membresias-'.$this->membership->year.'.pdf',
            )->withMime('application/pdf'),
        ];
    }

    private function viewData(): array
    {
        return [
            'membership' => $this->membership,
            'renewals' => $this->renewals,
            'generatedBy' => $this->generatedBy,
            'generatedAt' => now(),
            'logo' => is_file(FabPath::logo()) ? FabPath::logo() : null,
            'footerImage' => is_file(FabPath::footer()) ? FabPath::footer() : null,
            'systemName' => 'Sistema Administrativo Federación Argentina de Billar',
            'systemVersion' => config('app.version'),
        ];
    }
}
