<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar');
            $table->text('description');
            $table->text('description_ar');
            $table->decimal('price', 8, 2)->nullable();
            $table->integer('stock');
            $table->string('image_url')->nullable();
            $table->string('category_ar');
            $table->unsignedBigInteger('alternative_medicine_id')->nullable(); // Reference to an alternative medicine
            $table->foreign('alternative_medicine_id')->references('id')->on('medicines')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('medicines');
    }
};
