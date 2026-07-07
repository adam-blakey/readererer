<?php

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
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignIdFor(TermDate::class)->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('email_recipients', function (Blueprint $table) {
            $table->foreignIdFor(User::class)->nullable()->after('email_log_id')->constrained()->nullOnDelete();
            $table->string('name')->nullable()->after('user_id');
            $table->string('email')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_recipients', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(User::class);
            $table->dropColumn(['name', 'email']);
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(TermDate::class);
        });
    }
};
