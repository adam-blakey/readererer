<?php

use App\Models\TermDate;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(User::class, 'edit_user_id');
            $table->foreignIdFor(TermDate::class);
            $table->integer('status')->nullable();
            $table->ipAddress('edit_ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
