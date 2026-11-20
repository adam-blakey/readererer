<?php

use App\Models\DressCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dress_codes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('term_dates', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->text('concert_order')->nullable();
            $table->foreignIdFor(DressCode::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(DressCode::class);
            $table->dropColumn(['description', 'concert_order']);
        });

        Schema::dropIfExists('dress_codes');
    }
};
