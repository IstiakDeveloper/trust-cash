<?php

namespace App\Traits;

use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

trait ManagesStock
{
    public function getCurrentStock($productId)
    {
        return DB::table('inventory_balances')->where('product_id', $productId)->sum('quantity');
    }

    public function updateProductStock($productId, $quantity, $type, $referenceType, $referenceId)
    {
        $currentStock = $this->getCurrentStock($productId);
        $newQuantity = $type === 'in' ? $currentStock + $quantity : $currentStock - $quantity;

        if ($newQuantity < 0) {
            throw new \Exception("Insufficient stock for product ID: {$productId}");
        }

        // Create stock movement record
        StockMovement::create([
            'product_id' => $productId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'before_quantity' => $currentStock,
            'after_quantity' => $newQuantity,
            'type' => $type,
            'created_by' => auth()->id(),
        ]);

        return $newQuantity;
    }
}
