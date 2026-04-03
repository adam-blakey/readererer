<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_post_code')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_address_line1')->nullable();
            $table->string('emergency_contact_address_line2')->nullable();
            $table->string('emergency_contact_address_city')->nullable();
            $table->string('emergency_contact_address_post_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('has_photo_permission')->default(false);
            $table->boolean('is_gift_aiding_subs')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_number');
            $table->dropColumn('address_line1');
            $table->dropColumn('address_line2');
            $table->dropColumn('address_city');
            $table->dropColumn('address_post_code');
            $table->dropColumn('emergency_contact_name');
            $table->dropColumn('emergency_contact_number');
            $table->dropColumn('emergency_contact_relationship');
            $table->dropColumn('emergency_contact_address_line1');
            $table->dropColumn('emergency_contact_address_line2');
            $table->dropColumn('emergency_contact_address_city');
            $table->dropColumn('emergency_contact_address_post_code');
            $table->dropColumn('date_of_birth');
            $table->dropColumn('has_photo_permission');
            $table->dropColumn('is_gift_aiding_subs');
        });
    }
};