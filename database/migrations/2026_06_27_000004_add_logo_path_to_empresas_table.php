<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('empresas', 'logo_path')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('correo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('empresas', 'logo_path')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }
};
