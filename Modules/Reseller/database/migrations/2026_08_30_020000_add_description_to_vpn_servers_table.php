<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            if (!Schema::hasColumn('vpn_servers', 'description')) {
                $table->text('description')->nullable()->after('config');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vpn_servers', function (Blueprint $table) {
            if (Schema::hasColumn('vpn_servers', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
