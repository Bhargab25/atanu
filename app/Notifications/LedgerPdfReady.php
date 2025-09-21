<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LedgerPdfReady extends Notification
{
    use Queueable;

    protected $entity;
    protected $filePath;
    protected $filename;
    protected $entityType;

    public function __construct($entity, $filePath, $filename, $entityType = 'supplier')
    {
        $this->entity = $entity;
        $this->filePath = $filePath;
        $this->filename = $filename;
        $this->entityType = $entityType; // 'supplier' or 'client'
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // Added mail channel
    }

    public function toMail($notifiable)
    {
        $entityName = ucfirst($this->entityType);
        $downloadUrl = route('download.ledger.pdf', ['path' => base64_encode($this->filePath)]);

        return (new MailMessage)
            ->subject("{$entityName} Ledger PDF Ready")
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("The ledger PDF for {$this->entity->name} has been generated successfully.")
            ->action('Download PDF', $downloadUrl)
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => ucfirst($this->entityType) . ' Ledger PDF Ready!',
            'body' => "{$this->entityType} ledger PDF for {$this->entity->name} has been generated successfully.",
            'entity_id' => $this->entity->id,
            'entity_type' => $this->entityType,
            'entity_name' => $this->entity->name,
            'file_path' => $this->filePath,
            'filename' => $this->filename,
            'download_url' => route('download.ledger.pdf', ['path' => base64_encode($this->filePath)])
        ];
    }

    // Optional: Add a method to get the icon based on entity type
    public function getIcon()
    {
        return $this->entityType === 'supplier' ? 'truck' : 'users';
    }
}
