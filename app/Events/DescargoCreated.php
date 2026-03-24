<?php

namespace App\Events;

use App\Models\Descargo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DescargoCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $descargo;

    /**
     * Create a new event instance.
     */
    public function __construct(Descargo $descargo)
    {
        $this->descargo = $descargo;
    }
}
