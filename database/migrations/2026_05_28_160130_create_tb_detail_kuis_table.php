<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_detail_kuis', function (Blueprint $table) {

            $table->string('gambar_pertanyaan')
                ->nullable()
                ->after('pertanyaan');

            $table->string('gambar_pilihan_a')
                ->nullable()
                ->after('pilihan_a');

            $table->string('gambar_pilihan_b')
                ->nullable()
                ->after('pilihan_b');

            $table->string('gambar_pilihan_c')
                ->nullable()
                ->after('pilihan_c');

            $table->string('gambar_pilihan_d')
                ->nullable()
                ->after('pilihan_d');
        });
    }

    public function down(): void
    {
        Schema::table('tb_detail_kuis', function (Blueprint $table) {

            $table->dropColumn([
                'gambar_pertanyaan',
                'gambar_pilihan_a',
                'gambar_pilihan_b',
                'gambar_pilihan_c',
                'gambar_pilihan_d',
            ]);

        });
    }
};
