<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportGenerationCompleted extends Notification
{
    use Queueable;

    protected $results;
    protected $success;

    public function __construct($results, $success = true)
    {
        $this->results = $results;
        $this->success = $success;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->success) {
            return [
                'title' => ucfirst($this->results['report_type']) . ' Report Ready!',
                'body' => "Your {$this->results['report_type']} report has been generated successfully and is ready for download.",
                'type' => 'success',
                'icon' => 'o-document-arrow-down',
                'report_type' => $this->results['report_type'],
                'filename' => $this->results['filename'],
                'file_url' => $this->results['file_url'],
                'file_path' => $this->results['file_path'],
                'file_size' => $this->results['file_size'],
                'generated_at' => $this->results['generated_at'],
                'download_url' => url($this->results['file_url']),
                'expires_at' => now()->addDays(7)->toDateTimeString(), // Auto-delete after 7 days
            ];
        } else {
            return [
                'title' => ucfirst($this->results['report_type']) . ' Report Failed!',
                'body' => 'Report generation failed: ' . ($this->results['error'] ?? 'Unknown error occurred.'),
                'type' => 'error',
                'icon' => 'o-exclamation-triangle',
                'report_type' => $this->results['report_type'],
                'error_message' => $this->results['error'] ?? 'Unknown error',
                'failed_at' => $this->results['failed_at'] ?? now()->toDateTimeString(),
            ];
        }
    }
}
