<?php

namespace App\Contracts;

use App\Models\Ticket;

interface DeliveryClientInterface
{
    public function send(Ticket $ticket): DeliveryResult;
}
