<?php

use App\Models\InstrumentFamily;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->foreignIdFor(InstrumentFamily::class);
        });
    }

    public function down(): void {}
};
