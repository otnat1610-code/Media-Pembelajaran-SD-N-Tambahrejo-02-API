<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKuis extends Model
{
    protected $table = 'tb_detail_kuis';

    protected $primaryKey = 'id_detail_kuis';

    public $timestamps = true;

    protected $fillable = [

        // =========================
        // RELASI
        // =========================
        'id_kuis',

        // =========================
        // SOAL
        // =========================
        'pertanyaan',
        'gambar_pertanyaan',

        // =========================
        // PILIHAN A
        // =========================
        'pilihan_a',
        'gambar_pilihan_a',

        // =========================
        // PILIHAN B
        // =========================
        'pilihan_b',
        'gambar_pilihan_b',

        // =========================
        // PILIHAN C
        // =========================
        'pilihan_c',
        'gambar_pilihan_c',

        // =========================
        // PILIHAN D
        // =========================
        'pilihan_d',
        'gambar_pilihan_d',

        // =========================
        // JAWABAN
        // =========================
        'jawaban',

        // =========================
        // POIN
        // =========================
        'poin',
    ];

    // =========================
    // RELASI KE KUIS
    // =========================
    public function kuis()
    {
        return $this->belongsTo(
            Kuis::class,
            'id_kuis',
            'id_kuis'
        );
    }
}
