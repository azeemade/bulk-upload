<?php

namespace Azeemade\BulkUpload\Notifications;

use Azeemade\BulkUpload\Models\BulkUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkUploadProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public $bulkUpload;

    public function __construct(BulkUpload $bulkUpload)
    {
        $this->bulkUpload = $bulkUpload;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Bulk Upload Processed')
            ->line('Your bulk upload for ' . $this->bulkUpload->model_class . ' has been processed.')
            ->line('Status: ' . $this->bulkUpload->status)
            ->line('Total Rows: ' . $this->bulkUpload->total_rows)
            ->line('Successful: ' . $this->bulkUpload->successful_rows)
            ->line('Failed: ' . $this->bulkUpload->failed_rows)
            ->action('View Details', url('/')) // In real app, link to dashboard
            ->line('Thank you for using our application!');
    }
}
