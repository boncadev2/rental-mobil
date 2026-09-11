<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('customers', fn (Blueprint $table) => $table->string('ktp_file_path')->nullable()->after('address')); }
    public function down(): void { Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('ktp_file_path')); }
};
