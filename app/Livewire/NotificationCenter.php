<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;


class NotificationCenter extends Component
{
    public $showDrawer = false;
    public $notifications = [];
    public $unreadCount = 0;

    protected $listeners = ['refreshNotifications' => 'loadNotifications'];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            $this->notifications = Auth::user()
                ->notifications()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $this->unreadCount = Auth::user()->unreadNotifications()->count();
        }
    }

    public function openDrawer()
    {
        $this->showDrawer = true;
        $this->loadNotifications();
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function clearAllNotifications()
    {
        Auth::user()->notifications()->delete();
        $this->loadNotifications();
    }


    public function downloadFile($url, $filename)
    {
        $this->dispatch('download-file', ['url' => $url, 'filename' => $filename]);
    }

    public function render()
    {
        return view('livewire.notification-center');
    }
}
