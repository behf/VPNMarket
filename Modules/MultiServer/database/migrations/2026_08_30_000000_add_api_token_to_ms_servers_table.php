<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ms_servers', function (Blueprint $table) {
            if (!Schema::hasColumn('ms_servers', 'api_token')) {
                $table->text('api_token')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ms_servers', function (Blueprint $table) {
            if (Schema::hasColumn('ms_servers', 'api_token')) {
                $table->dropColumn('api_token');
            }
        });
    }
};
