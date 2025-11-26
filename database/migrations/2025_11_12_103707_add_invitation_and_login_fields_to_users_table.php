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
            //
            $table->timestamp('invited_at')->nullable()->after('remember_token');
            $table->unsignedSmallInteger('invitation_sent_count')->default(0)->after('invited_at');
            $table->timestamp('last_login_at')->nullable()->after('invitation_sent_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn(['invited_at','invitation_sent_count','last_login_at']);
        });
    }
};
