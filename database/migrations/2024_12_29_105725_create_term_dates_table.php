<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Term;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('term_dates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->datetime('start_datetime');
            $table->datetime('end_datetime');
            $table->foreignIdFor(Term::class);
            $table->boolean('is_concert')->default(false);
            $table->boolean('show')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_dates');
    }
};