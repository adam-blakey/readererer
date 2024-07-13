<?php

use App\Models\Part;
use App\Models\Piece;
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
        Schema::create('part_piece', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Piece::class);
            $table->foreignIdFor(Part::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_piece');
    }
};
