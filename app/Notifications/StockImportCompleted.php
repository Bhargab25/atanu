<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockImportCompleted extends Notification
{
    use Queueable;

    protected $results;
    protected $success;

    /**
     * Create a new notification instance.
     */
    public function __construct($results, $success = true)
    {
        $this->results = $results;
        $this->success = $success;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        if ($this->success) {
            return [
                'title' => 'Stock Import Completed!',
                'body' => "Successfully imported {$this->results['successful']} out of {$this->results['total_rows']} items.",
                'type' => 'success',
                'icon' => 'o-check-circle',
                'total_rows' => $this->results['total_rows'],
                'successful' => $this->results['successful'],
                'failed' => $this->results['failed'],
                'success_rate' => round(($this->results['successful'] / $this->results['total_rows']) * 100, 1),
                'errors' => $this->results['errors'] ?? [],
            ];
        } else {
            return [
                'title' => 'Stock Import Failed!',
                'body' => 'Stock import failed: ' . ($this->results['error'] ?? 'Unknown error occurred.'),
                'type' => 'error',
                'icon' => 'o-x-circle',
                'error_message' => $this->results['error'] ?? 'Unknown error',
            ];
        }
    }
}
