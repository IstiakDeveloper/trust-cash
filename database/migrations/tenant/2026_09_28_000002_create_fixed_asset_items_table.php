<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixed_asset_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->string('item_name'); // e.g. মনিটর, মাউস, কিবোর্ড, সিপিইউ, ইউপিএস, ডিসপ্লে র্যাক
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
            $table->string('status')->default('active'); // active, disposed, damaged
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_items');
    }
};
