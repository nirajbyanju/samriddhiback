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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete()->nullable();
            $table->unsignedInteger('inquiry_type_id'); // 1 - buy, 2 - sell
            $table->unsignedInteger('property_type_id')->nullable(); // 1 - house, 2 - land, 3 - commercial

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('preferred_location')->nullable();

            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();



            $table->text('message')->nullable();

            $table->status();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
