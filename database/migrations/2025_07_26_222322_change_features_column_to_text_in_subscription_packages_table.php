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
        Schema::table('subscription_packages', function (Blueprint $table) {
            // First, convert existing JSON features to comma-separated string
            $packages = \App\Models\SubscriptionPackage::all();
            foreach ($packages as $package) {
                if (is_array($package->features)) {
                    $package->features = implode(',', $package->features);
                    $package->save();
                }
            }
            
            // Change column type from JSON to TEXT
            $table->text('features')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            // Convert back to JSON if needed
            $packages = \App\Models\SubscriptionPackage::all();
            foreach ($packages as $package) {
                if (is_string($package->features)) {
                    $features = array_map('trim', explode(',', $package->features));
                    $package->features = json_encode($features);
                    $package->save();
                }
            }
            
            $table->json('features')->change();
        });
    }
};
