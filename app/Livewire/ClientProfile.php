<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\ProductCategory;

class ClientProfile extends Component
{
    public Client $client;
    public $serviceDetails = [];

    public function mount($clientId)
    {
        $this->client = Client::findOrFail($clientId);
        $this->loadServiceDetails();
    }

    private function loadServiceDetails()
    {
        if ($this->client->services_items) {
            $serviceIds = array_keys($this->client->services_items);
            $services = ProductCategory::with('activeItems')->whereIn('id', $serviceIds)->get();

            foreach ($services as $service) {
                $clientServiceData = $this->client->services_items[$service->id] ?? [];
                $this->serviceDetails[] = [
                    'service' => $service,
                    'items' => $clientServiceData['items'] ?? [],
                    'total' => collect($clientServiceData['items'] ?? [])->sum(function ($item) {
                        return ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                    })
                ];
            }
        }
    }

    public function render()
    {
        return view('livewire.client-profile');
    }
}
