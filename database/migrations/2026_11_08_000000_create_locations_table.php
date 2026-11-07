<?php

use App\Models\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('term_dates', function (Blueprint $table) {
            $table->boolean('time_tbc')->default(false);
            $table->foreignIdFor(Location::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('term_dates', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Location::class);
            $table->dropColumn('time_tbc');
        });

        Schema::dropIfExists('locations');
    }
};
