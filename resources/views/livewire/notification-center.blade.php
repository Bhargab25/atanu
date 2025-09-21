<div>
    {{-- Poll for new notifications only when drawer is open --}}
    @if($showDrawer)
    <div wire:poll.10s="loadNotifications" class="hidden"></div>
    @endif

    {{-- Notification Bell Button --}}
    <button
        wire:click="openDrawer"
        class="relative btn btn-ghost btn-sm btn-circle">
        <x-mary-icon name="o-bell" class="w-5 h-5" />
        @if($unreadCount > 0)
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
        </span>
        @endif
    </button>

    {{-- Notification Drawer --}}
    <x-mary-drawer
        wire:model="showDrawer"
        title="Notifications"
        subtitle="Your recent notifications"
        separator
        with-close-button
        close-on-escape
        class="w-11/12 lg:w-1/3"
        right>

        {{-- Stats Bar --}}
        <div class="flex items-center gap-4 p-3 bg-base-100 rounded border mb-4">
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-info rounded-full"></div>
                <span class="text-sm">{{ $notifications->count() }} Total</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-warning rounded-full"></div>
                <span class="text-sm">{{ $unreadCount }} Unread</span>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-2 mb-4">
            @if($unreadCount > 0)
                <x-mary-button
                    wire:click="markAllAsRead"
                    label="Mark All Read"
                    icon="o-check-circle"
                    class="btn-sm btn-outline"
                    spinner="markAllAsRead" />
            @endif
            
            @if($notifications->count() > 0)
                <x-mary-button
                    wire:click="clearAllNotifications"
                    label="Clear All"
                    icon="o-trash"
                    class="btn-sm btn-outline btn-error"
                    spinner="clearAllNotifications"
                    onclick="return confirm('Are you sure you want to delete all notifications?')" />
            @endif
        </div>

        {{-- Notifications List --}}
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="p-4 border rounded-lg {{ $notification->read_at ? 'bg-gray-50' : 'bg-blue-50 border-blue-200' }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-semibold text-sm">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </h4>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $notification->data['body'] ?? '' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1 ml-4">
                            @if(isset($notification->data['download_url']))
                                <x-mary-button
                                    wire:click="downloadFile('{{ $notification->data['download_url'] }}', '{{ $notification->data['filename'] ?? 'file.pdf' }}')"
                                    icon="o-arrow-down-tray"
                                    class="btn-xs btn-primary"
                                    label="Download" />
                            @endif
                            
                            @if(!$notification->read_at)
                                <x-mary-button
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    icon="o-check"
                                    class="btn-xs btn-ghost"
                                    label="Mark Read" />
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <x-mary-icon name="o-bell-slash" class="w-12 h-12 mx-auto mb-2" />
                    <p>No notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Actions --}}
        <x-slot:actions>
            <x-mary-button
                label="Refresh"
                wire:click="loadNotifications"
                class="btn-ghost btn-sm"
                spinner="loadNotifications" />
            <x-mary-button
                label="Close"
                @click="$wire.showDrawer = false"
                class="btn-sm" />
        </x-slot:actions>
    </x-mary-drawer>
</div>

{{-- Download Handler Script --}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('download-file', (data) => {
            const link = document.createElement('a');
            link.href = data[0].url;
            link.download = data[0].filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
</script>
