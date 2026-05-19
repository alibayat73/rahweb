<?php

namespace App\Infrastructure\Adapters;

use App\Contracts\DeliveryClientInterface;
use App\Contracts\DeliveryResult;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;

class MockWebserviceAdapter implements DeliveryClientInterface
{
    public function send(Ticket $ticket): DeliveryResult
    {
        $response = Http::timeout(10)
            ->get(url('/api/mock-webservice'), [
                'ticket_id' => $ticket->id,
                'title' => $ticket->title,
            ]);

        if ($response->successful()) {
            return DeliveryResult::success($response->status(), $response->body());
        }

        return DeliveryResult::failure(
            $response->status(),
            $response->body() ?: 'Webservice returned error'
        );
    }
}
