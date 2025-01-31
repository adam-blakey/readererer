<?php

use App\Models\Piece;
use App\Models\Setlist;
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
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('setlist_piece', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Setlist::class);
            $table->foreignIdFor(Piece::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlists');
        Schema::dropIfExists('setlist_pieces');
    }
};