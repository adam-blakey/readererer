<?php

use App\Models\InstrumentFamily;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_ensemble', function (Blueprint $table) {
            $table->foreignIdFor(InstrumentFamily::class)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_ensemble', function (Blueprint $table) {
            $table->foreignIdFor(InstrumentFamily::class)->change();
        });
    }
};
