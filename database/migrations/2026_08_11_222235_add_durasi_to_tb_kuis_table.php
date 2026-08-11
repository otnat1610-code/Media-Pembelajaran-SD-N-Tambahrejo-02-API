<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_kuis', function (Blueprint $table) {
            $table->integer('durasi')
                ->default(10)
                ->after('total_soal');
        });
    }

    public function down(): void
    {
        Schema::table('tb_kuis', function (Blueprint $table) {
            $table->dropColumn('durasi');
        });
    }
};
