<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Enquiry Received - #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'admin.orders.invoice',
            with: [
                'is_email_or_pdf' => true,
                'is_pdf_render' => false,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', [
                'order' => $this->order,
                'is_email_or_pdf' => true,
                'is_pdf_render' => true,
            ])->setPaper('a4', 'portrait')->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => false,
                'isFontSubsettingEnabled' => false,
                'defaultFont' => 'sans-serif',
            ]);

            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $pdf->output(), 'enquiry-' . $this->order->order_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create PDF attachment for order #' . $this->order->id . ': ' . $e->getMessage());
            return [];
        }
    }
}
