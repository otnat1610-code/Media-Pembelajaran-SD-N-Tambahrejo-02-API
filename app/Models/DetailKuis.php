<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKuis extends Model
{
    protected $table = 'tb_detail_kuis';

    protected $primaryKey = 'id_detail_kuis';

    public $timestamps = true;

    protected $fillable = [
        'id_kuis',

        'pertanyaan',
        'gambar_pertanyaan',

        'pilihan_a',
        'gambar_pilihan_a',

        'pilihan_b',
        'gambar_pilihan_b',

        'pilihan_c',
        'gambar_pilihan_c',

        'pilihan_d',
        'gambar_pilihan_d',

        'jawaban',
        'poin',
    ];

    public function kuis()
    {
        return $this->belongsTo(
            Kuis::class,
            'id_kuis',
            'id_kuis'
        );
    }
}
