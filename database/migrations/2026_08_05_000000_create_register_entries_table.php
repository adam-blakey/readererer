<?php

use App\Enums\RegisterStatus;
use App\Models\Ensemble;
use App\Models\TermDate;
use App\Models\User;
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
        Schema::create('register_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(TermDate::class)->constrained();
            $table->foreignIdFor(Ensemble::class)->constrained();
            $table->integer('status')->default(RegisterStatus::Unmarked->value);
            $table->string('notes')->nullable();
            $table->foreignIdFor(User::class, 'marked_by_user_id')->nullable()->constrained('users');

            // Unlike the append-only attendance poll, a register holds one
            // current record per member per date, updated in place.
            $table->unique(['term_date_id', 'ensemble_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('register_entries');
    }
};
