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
        Schema::table('medicines', function (Blueprint $table) {
            // Add the 'alternative_medicine_id' column and set it as a foreign key
            $table->unsignedBigInteger('alternative_medicine_id')->nullable()->after('stock');
            $table->foreign('alternative_medicine_id')->references('id')->on('medicines')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('medicines', function (Blueprint $table) {
            // Remove the foreign key and then the column
            $table->dropForeign(['alternative_medicine_id']);
            $table->dropColumn('alternative_medicine_id');
        });
    }
};

