<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Ensemble;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ensemble_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Ensemble::class);
            $table->foreignIdFor(User::class, 'admin_id');
            $table->unique(['ensemble_id', 'admin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ensemble_admins');
    }
};
