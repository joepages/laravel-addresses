<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship
            $table->string('addressable_type');
            $table->unsignedBigInteger('addressable_id');

            // Address classification
            $table->string('type', 50)->default('home');
            $table->boolean('is_primary')->default(false);

            // Address fields
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 3);

            // Geolocation
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Extensibility
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['addressable_type', 'addressable_id'], 'addresses_addressable_index');
            $table->index('type');
            $table->index('is_primary');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
