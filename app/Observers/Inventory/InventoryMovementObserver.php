<?php

namespace App\Observers\Inventory;

use App\Models\Inventory\InventoryMovement;
use App\Jobs\Inventory\UpdateInventoryFromMovement;

class InventoryMovementObserver
{
    public function created(InventoryMovement $movement): void
    {
        // Dispatch job to update inventory levels
        UpdateInventoryFromMovement::dispatch($movement->id);
    }
}