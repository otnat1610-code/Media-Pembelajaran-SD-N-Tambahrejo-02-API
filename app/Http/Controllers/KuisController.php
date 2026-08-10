<?php

namespace App\Http\Controllers;

use App\Models\Kuis;
use App\Models\DetailKuis;
use App\Models\JumlahSoal;
use App\Models\JawabanSiswa;
use App\Models\PerolehanNilai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KuisController extends Controller
{
    // =====================================================
    // HELPER URL GAMBAR
    // =====================================================

    private function imageUrl($path)
    {
        if (!$path) {
            return null;
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }


    // =====================================================
    // FORMAT DETAIL SOAL UNTUK SISWA
    // =====================================================

    private function formatDetail($detail)
    {
        return [
            'id_detail_kuis' => $detail->id_detail_kuis,

            'q' => $detail->pertanyaan ?? '',

            'gambar_pertanyaan' =>
                $this->imageUrl(
                    $detail->gambar_pertanyaan
                ),

            'options' => [
                [
                    'key' => 'A',
                    'text' => $detail->pilihan_a ?? '',
                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_a
                        ),
                ],
                [
                    'key' => 'B',
                    'text' => $detail->pilihan_b ?? '',
                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_b
                        ),
                ],
                [
                    'key' => 'C',
                    'text' => $detail->pilihan_c ?? '',
                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_c
                        ),
                ],
                [
                    'key' => 'D',
                    'text' => $detail->pilihan_d ?? '',
                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_d
                        ),
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Jawaban benar
            |--------------------------------------------------------------------------
            |
            | Nilainya tetap A/B/C/D.
            | Jadi pilihan boleh berupa gambar saja,
            | tetapi jawaban benar tetap menunjuk pilihan tersebut.
            |
            */

            'answer' => $detail->jawaban,

            'poin' => $detail->poin ?? 10,
        ];
    }


    // =====================================================
    // NORMALISASI JAWABAN
    // =====================================================

    private function normalizeAnswer($jawaban)
    {
        if ($jawaban === null) {
            return null;
        }

        $jawaban = strtoupper(trim((string) $jawaban));

        /*
        |--------------------------------------------------------------------------
        | Bentuk normal
        |--------------------------------------------------------------------------
        */

        if (in_array($jawaban, ['A', 'B', 'C', 'D'])) {
            return $jawaban;
        }

        /*
        |--------------------------------------------------------------------------
        | Jika frontend mengirim angka
        |--------------------------------------------------------------------------
        |
        | 0 = A
        | 1 = B
        | 2 = C
        | 3 = D
        |
        */

        if (in_array($jawaban, ['0', '1', '2', '3'])) {
            return [
                '0' => 'A',
                '1' => 'B',
                '2' => 'C',
                '3' => 'D',
            ][$jawaban];
        }

        /*
        |--------------------------------------------------------------------------
        | Jika frontend mengirim "pilihan_a", dll.
        |--------------------------------------------------------------------------
        */

        $mapping = [
            'PILIHAN_A' => 'A',
            'PILIHAN_B' => 'B',
            'PILIHAN_C' => 'C',
            'PILIHAN_D' => 'D',

            'OPTION_A' => 'A',
            'OPTION_B' => 'B',
            'OPTION_C' => 'C',
            'OPTION_D' => 'D',

            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
            'D' => 'D',
        ];

        return $mapping[$jawaban] ?? null;
    }


    // =====================================================
    // VALIDASI ISI SOAL
    // =====================================================

    private function validateQuestionContent(
        Request $request,
        $index,
        $soal
    ) {
        /*
        |--------------------------------------------------------------------------
        | PERTANYAAN
        |--------------------------------------------------------------------------
        |
        | Pertanyaan boleh:
        | 1. teks
        | 2. gambar
        | 3. teks + gambar
        |
        */

        $pertanyaan =
            trim(
                $soal['pertanyaan'] ?? ''
            );

        $hasGambarPertanyaan =
            $request->hasFile(
                "soal.$index.gambar_pertanyaan"
            );

        if (
            $pertanyaan === '' &&
            !$hasGambarPertanyaan
        ) {
            return 'Pertanyaan harus diisi atau diberikan gambar.';
        }


        /*
        |--------------------------------------------------------------------------
        | PILIHAN A-D
        |--------------------------------------------------------------------------
        |
        | Setiap pilihan boleh:
        | 1. teks
        | 2. gambar
        | 3. teks + gambar
        |
        */

        foreach (
            ['A', 'B', 'C', 'D']
            as $option
        ) {

            $text =
                trim(
                    $soal['pilihan'][$option]
                    ?? ''
                );

            $hasImage =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.$option"
                );

            /*
            |--------------------------------------------------------------------------
            | Saat EDIT, gambar lama juga harus dianggap tersedia.
            |--------------------------------------------------------------------------
            */

            $idDetail =
                $soal['id_detail_kuis']
                ?? null;

            $detailLama =
                $idDetail
                    ? DetailKuis::find($idDetail)
                    : null;

            $field =
                'gambar_pilihan_' .
                strtolower($option);

            $hasOldImage =
                $detailLama &&
                !empty($detailLama->$field) &&
                empty(
                    $soal['hapus_gambar_pilihan'][$option]
                    ?? false
                );

            if (
                $text === '' &&
                !$hasImage &&
                !$hasOldImage
            ) {
                return
                    "Pilihan $option harus diisi atau diberikan gambar.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | JAWABAN BENAR
        |--------------------------------------------------------------------------
        |
        | Jawaban benar TIDAK perlu berupa teks.
        |
        | Yang dibutuhkan hanya penanda A/B/C/D.
        |
        */

        $jawaban =
            $this->normalizeAnswer(
                $soal['jawaban']
                ?? null
            );

        if (!$jawaban) {
            return
                'Silakan pilih jawaban yang benar (A, B, C, atau D).';
        }


        return null;
    }


    // =====================================================
    // DATA KUIS SISWA
    // =====================================================

    public function index()
    {
        $kuis =
            Kuis::with('detailKuis')
                ->where('status', 'aktif')
                ->get();

        $formatted = [];

        foreach ($kuis as $item) {

            foreach (
                $item->detailKuis
                as $detail
            ) {

                $data =
                    $this->formatDetail(
                        $detail
                    );

                $data['judul_kuis'] =
                    $item->judul;

                $data['id_kuis'] =
                    $item->id_kuis;

                $formatted[] =
                    $data;
            }
        }

        return response()->json(
            $formatted
        );
    }


    // =====================================================
    // SOAL BERDASARKAN KUIS
    // =====================================================

    public function soalByKuis($id)
    {
        $setting =
            JumlahSoal::first();

        $jmlSoal =
            $setting
                ? $setting->jml_soal
                : null;

        $query =
            DetailKuis::where(
                'id_kuis',
                $id
            )
            ->inRandomOrder();

        if ($jmlSoal) {
            $query->limit($jmlSoal);
        }

        $soal =
            $query->get();

        $formatted =
            $soal->map(
                function ($item) {
                    return $this->formatDetail(
                        $item
                    );
                }
            );

        return response()->json(
            $formatted->values()
        );
    }


    // =====================================================
    // LIST KUIS AKTIF
    // =====================================================

    public function listKuis()
    {
        return Kuis::select(
            'id_kuis',
            'judul',
            'deskripsi'
        )
            ->where(
                'status',
                'aktif'
            )
            ->get();
    }


    // =====================================================
    // DATA KUIS ADMIN
    // =====================================================

    public function adminIndex()
    {
        $kuis =
            Kuis::with('detailKuis')
                ->orderBy(
                    'id_kuis',
                    'desc'
                )
                ->get();

        $kuis->each(
            function ($item) {

                $item->detailKuis->each(
                    function ($detail) {

                        /*
                        |--------------------------------------------------------------------------
                        | PATH ASLI
                        |--------------------------------------------------------------------------
                        */

                        $detail->gambar_pertanyaan_path =
                            $detail->gambar_pertanyaan;

                        $detail->gambar_pilihan_a_path =
                            $detail->gambar_pilihan_a;

                        $detail->gambar_pilihan_b_path =
                            $detail->gambar_pilihan_b;

                        $detail->gambar_pilihan_c_path =
                            $detail->gambar_pilihan_c;

                        $detail->gambar_pilihan_d_path =
                            $detail->gambar_pilihan_d;


                        /*
                        |--------------------------------------------------------------------------
                        | URL GAMBAR
                        |--------------------------------------------------------------------------
                        */

                        $detail->gambar_pertanyaan =
                            $this->imageUrl(
                                $detail->gambar_pertanyaan
                            );

                        $detail->gambar_pilihan_a =
                            $this->imageUrl(
                                $detail->gambar_pilihan_a
                            );

                        $detail->gambar_pilihan_b =
                            $this->imageUrl(
                                $detail->gambar_pilihan_b
                            );

                        $detail->gambar_pilihan_c =
                            $this->imageUrl(
                                $detail->gambar_pilihan_c
                            );

                        $detail->gambar_pilihan_d =
                            $this->imageUrl(
                                $detail->gambar_pilihan_d
                            );
                    }
                );
            }
        );

        return response()->json(
            $kuis
        );
    }


    // =====================================================
    // SIMPAN GAMBAR
    // =====================================================

    private function storeImage($file)
    {
        if (!$file) {
            return null;
        }

        return $file->store(
            'kuis',
            'public'
        );
    }


    // =====================================================
    // TAMBAH KUIS
    // =====================================================

    public function store(
        Request $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | VALIDASI DASAR
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'judul' =>
                'required|string',

            'status' =>
                'required|in:aktif,draft',

            'soal' =>
                'required|array|min:5',

            'soal.*.pertanyaan' =>
                'nullable|string',

            'soal.*.pilihan.A' =>
                'nullable|string',

            'soal.*.pilihan.B' =>
                'nullable|string',

            'soal.*.pilihan.C' =>
                'nullable|string',

            'soal.*.pilihan.D' =>
                'nullable|string',

            /*
            |--------------------------------------------------------------------------
            | PENTING
            |--------------------------------------------------------------------------
            |
            | Jangan menggunakan required|in di sini.
            | Kita normalisasi dan validasi secara manual.
            |
            */

            'soal.*.jawaban' =>
                'nullable',

            'soal.*.gambar_pertanyaan' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.A' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.B' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.C' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.D' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDASI SETIAP SOAL
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->soal
            as $index => $soal
        ) {

            $error =
                $this->validateQuestionContent(
                    $request,
                    $index,
                    $soal
                );

            if ($error) {

                return response()->json([
                    'message' =>
                        $error,

                    'field' =>
                        "soal.$index",

                    'soal_index' =>
                        $index,
                ], 422);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | BUAT KUIS
        |--------------------------------------------------------------------------
        */

        $kuis =
            Kuis::create([
                'id_guru' =>
                    1,

                'judul' =>
                    $request->judul,

                'deskripsi' =>
                    $request->deskripsi,

                'status' =>
                    $request->status,

                'total_soal' =>
                    count($request->soal),
            ]);


        /*
        |--------------------------------------------------------------------------
        | SIMPAN SEMUA SOAL
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->soal
            as $index => $s
        ) {

            /*
            |--------------------------------------------------------------------------
            | GAMBAR PERTANYAAN
            |--------------------------------------------------------------------------
            */

            $gambarPertanyaan =
                null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                )
            ) {

                $gambarPertanyaan =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pertanyaan"
                        )
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | GAMBAR A-D
            |--------------------------------------------------------------------------
            */

            $gambarA =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'A'
                );

            $gambarB =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'B'
                );

            $gambarC =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'C'
                );

            $gambarD =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'D'
                );


            /*
            |--------------------------------------------------------------------------
            | NORMALISASI JAWABAN
            |--------------------------------------------------------------------------
            */

            $jawaban =
                $this->normalizeAnswer(
                    $s['jawaban']
                    ?? null
                );


            /*
            |--------------------------------------------------------------------------
            | SIMPAN DETAIL
            |--------------------------------------------------------------------------
            */

            DetailKuis::create([

                'id_kuis' =>
                    $kuis->id_kuis,

                'pertanyaan' =>
                    $s['pertanyaan']
                    ?? '',

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A']
                    ?? '',

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B']
                    ?? '',

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C']
                    ?? '',

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D']
                    ?? '',

                'gambar_pilihan_d' =>
                    $gambarD,

                /*
                |--------------------------------------------------------------------------
                | YANG DISIMPAN HANYA A/B/C/D
                |--------------------------------------------------------------------------
                */

                'jawaban' =>
                    $jawaban,

                'poin' =>
                    10,
            ]);
        }


        return response()->json([
            'message' =>
                'Kuis berhasil ditambahkan',

            'data' =>
                $kuis,
        ], 201);
    }


    // =====================================================
    // HELPER SIMPAN GAMBAR PILIHAN
    // =====================================================

    private function storeOptionImage(
        Request $request,
        $index,
        $option
    ) {

        $field =
            "soal.$index.gambar_pilihan.$option";

        if (
            !$request->hasFile($field)
        ) {
            return null;
        }

        return $this->storeImage(
            $request->file($field)
        );
    }


    // =====================================================
    // DETAIL KUIS
    // =====================================================

    public function show($id)
    {
        $kuis =
            Kuis::with(
                'detailKuis'
            )->findOrFail($id);

        $kuis->detailKuis->each(
            function ($detail) {

                $detail->gambar_pertanyaan =
                    $this->imageUrl(
                        $detail->gambar_pertanyaan
                    );

                $detail->gambar_pilihan_a =
                    $this->imageUrl(
                        $detail->gambar_pilihan_a
                    );

                $detail->gambar_pilihan_b =
                    $this->imageUrl(
                        $detail->gambar_pilihan_b
                    );

                $detail->gambar_pilihan_c =
                    $this->imageUrl(
                        $detail->gambar_pilihan_c
                    );

                $detail->gambar_pilihan_d =
                    $this->imageUrl(
                        $detail->gambar_pilihan_d
                    );
            }
        );

        return response()->json(
            $kuis
        );
    }


    // =====================================================
    // UPDATE KUIS
    // =====================================================

    public function update(
        Request $request,
        $id
    ) {

        $kuis =
            Kuis::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | VALIDASI DASAR
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'judul' =>
                'required|string',

            'status' =>
                'required|in:aktif,draft',

            'soal' =>
                'required|array|min:5',

            'soal.*.pertanyaan' =>
                'nullable|string',

            'soal.*.pilihan.A' =>
                'nullable|string',

            'soal.*.pilihan.B' =>
                'nullable|string',

            'soal.*.pilihan.C' =>
                'nullable|string',

            'soal.*.pilihan.D' =>
                'nullable|string',

            /*
            |--------------------------------------------------------------------------
            | TIDAK REQUIRED DI SINI
            |--------------------------------------------------------------------------
            */

            'soal.*.jawaban' =>
                'nullable',

            'soal.*.gambar_pertanyaan' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.A' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.B' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.C' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',

            'soal.*.gambar_pilihan.D' =>
                'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDASI SOAL
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->soal
            as $index => $soal
        ) {

            $idDetail =
                $soal['id_detail_kuis']
                ?? null;

            $detailLama =
                $idDetail
                    ? DetailKuis::find($idDetail)
                    : null;


            /*
            |--------------------------------------------------------------------------
            | PERTANYAAN
            |--------------------------------------------------------------------------
            */

            $pertanyaan =
                trim(
                    $soal['pertanyaan']
                    ?? ''
                );

            $hasNewQuestionImage =
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                );

            $hasOldQuestionImage =
                $detailLama &&
                !empty(
                    $detailLama->gambar_pertanyaan
                ) &&
                empty(
                    $soal[
                        'hapus_gambar_pertanyaan'
                    ] ?? false
                );

            if (
                $pertanyaan === '' &&
                !$hasNewQuestionImage &&
                !$hasOldQuestionImage
            ) {

                return response()->json([
                    'message' =>
                        'Pertanyaan harus diisi atau diberikan gambar.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | PILIHAN A-D
            |--------------------------------------------------------------------------
            */

            foreach (
                ['A', 'B', 'C', 'D']
                as $option
            ) {

                $text =
                    trim(
                        $soal['pilihan'][$option]
                        ?? ''
                    );

                $hasNewImage =
                    $request->hasFile(
                        "soal.$index.gambar_pilihan.$option"
                    );

                $field =
                    'gambar_pilihan_' .
                    strtolower($option);

                $hasOldImage =
                    $detailLama &&
                    !empty(
                        $detailLama->$field
                    ) &&
                    empty(
                        $soal[
                            'hapus_gambar_pilihan'
                        ][$option]
                        ?? false
                    );

                if (
                    $text === '' &&
                    !$hasNewImage &&
                    !$hasOldImage
                ) {

                    return response()->json([
                        'message' =>
                            "Pilihan $option harus diisi atau diberikan gambar.",
                    ], 422);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | JAWABAN
            |--------------------------------------------------------------------------
            */

            $jawaban =
                $this->normalizeAnswer(
                    $soal['jawaban']
                    ?? null
                );

            if (!$jawaban) {

                return response()->json([
                    'message' =>
                        "Soal nomor " .
                        ($index + 1) .
                        " belum memiliki jawaban benar. Silakan pilih A, B, C, atau D.",
                ], 422);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE KUIS
        |--------------------------------------------------------------------------
        */

        $kuis->update([

            'judul' =>
                $request->judul,

            'deskripsi' =>
                $request->deskripsi,

            'status' =>
                $request->status,

            'total_soal' =>
                count($request->soal),
        ]);


        /*
        |--------------------------------------------------------------------------
        | ID SOAL YANG MASIH DIPAKAI
        |--------------------------------------------------------------------------
        */

        $submittedIds =
            collect($request->soal)
                ->pluck(
                    'id_detail_kuis'
                )
                ->filter()
                ->map(
                    fn ($id) =>
                        (int) $id
                )
                ->values()
                ->toArray();


        /*
        |--------------------------------------------------------------------------
        | HAPUS SOAL YANG DIHILANGKAN
        |--------------------------------------------------------------------------
        */

        $soalLama =
            DetailKuis::where(
                'id_kuis',
                $id
            )->get();

        foreach (
            $soalLama
            as $lama
        ) {

            if (
                !in_array(
                    (int) $lama->id_detail_kuis,
                    $submittedIds
                )
            ) {

                $this->deleteDetailImages(
                    $lama
                );

                $lama->delete();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE / TAMBAH SOAL
        |--------------------------------------------------------------------------
        */

        foreach (
            $request->soal
            as $index => $s
        ) {

            $idDetail =
                $s['id_detail_kuis']
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | SOAL LAMA
            |--------------------------------------------------------------------------
            */

            if ($idDetail) {

                $detail =
                    DetailKuis::where(
                        'id_detail_kuis',
                        $idDetail
                    )
                    ->where(
                        'id_kuis',
                        $id
                    )
                    ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | GAMBAR PERTANYAAN
                |--------------------------------------------------------------------------
                */

                $gambarPertanyaan =
                    $detail->gambar_pertanyaan;

                if (
                    !empty(
                        $s[
                            'hapus_gambar_pertanyaan'
                        ]
                    )
                ) {

                    if (
                        $gambarPertanyaan
                    ) {

                        $this->deleteImage(
                            $gambarPertanyaan
                        );
                    }

                    $gambarPertanyaan =
                        null;
                }


                if (
                    $request->hasFile(
                        "soal.$index.gambar_pertanyaan"
                    )
                ) {

                    if (
                        $gambarPertanyaan
                    ) {

                        $this->deleteImage(
                            $gambarPertanyaan
                        );
                    }

                    $gambarPertanyaan =
                        $this->storeImage(
                            $request->file(
                                "soal.$index.gambar_pertanyaan"
                            )
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | GAMBAR PILIHAN
                |--------------------------------------------------------------------------
                */

                $gambarPilihan = [];


                foreach (
                    ['A', 'B', 'C', 'D']
                    as $option
                ) {

                    $field =
                        'gambar_pilihan_' .
                        strtolower($option);

                    $gambarPilihan[$option] =
                        $detail->$field;


                    /*
                    |--------------------------------------------------------------------------
                    | HAPUS GAMBAR
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty(
                            $s[
                                'hapus_gambar_pilihan'
                            ][$option]
                            ?? false
                        )
                    ) {

                        if (
                            $gambarPilihan[$option]
                        ) {

                            $this->deleteImage(
                                $gambarPilihan[$option]
                            );
                        }

                        $gambarPilihan[$option] =
                            null;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GANTI GAMBAR
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $request->hasFile(
                            "soal.$index.gambar_pilihan.$option"
                        )
                    ) {

                        if (
                            $gambarPilihan[$option]
                        ) {

                            $this->deleteImage(
                                $gambarPilihan[$option]
                            );
                        }

                        $gambarPilihan[$option] =
                            $this->storeImage(
                                $request->file(
                                    "soal.$index.gambar_pilihan.$option"
                                )
                            );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | JAWABAN
                |--------------------------------------------------------------------------
                */

                $jawaban =
                    $this->normalizeAnswer(
                        $s['jawaban']
                        ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | UPDATE DETAIL
                |--------------------------------------------------------------------------
                */

                $detail->update([

                    'pertanyaan' =>
                        $s['pertanyaan']
                        ?? '',

                    'gambar_pertanyaan' =>
                        $gambarPertanyaan,

                    'pilihan_a' =>
                        $s['pilihan']['A']
                        ?? '',

                    'gambar_pilihan_a' =>
                        $gambarPilihan['A'],

                    'pilihan_b' =>
                        $s['pilihan']['B']
                        ?? '',

                    'gambar_pilihan_b' =>
                        $gambarPilihan['B'],

                    'pilihan_c' =>
                        $s['pilihan']['C']
                        ?? '',

                    'gambar_pilihan_c' =>
                        $gambarPilihan['C'],

                    'pilihan_d' =>
                        $s['pilihan']['D']
                        ?? '',

                    'gambar_pilihan_d' =>
                        $gambarPilihan['D'],

                    'jawaban' =>
                        $jawaban,

                    'poin' =>
                        10,
                ]);

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | SOAL BARU SAAT EDIT
            |--------------------------------------------------------------------------
            */

            $gambarPertanyaan =
                null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                )
            ) {

                $gambarPertanyaan =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pertanyaan"
                        )
                    );
            }


            $gambarA =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'A'
                );

            $gambarB =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'B'
                );

            $gambarC =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'C'
                );

            $gambarD =
                $this->storeOptionImage(
                    $request,
                    $index,
                    'D'
                );


            $jawaban =
                $this->normalizeAnswer(
                    $s['jawaban']
                    ?? null
                );


            DetailKuis::create([

                'id_kuis' =>
                    $id,

                'pertanyaan' =>
                    $s['pertanyaan']
                    ?? '',

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A']
                    ?? '',

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B']
                    ?? '',

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C']
                    ?? '',

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D']
                    ?? '',

                'gambar_pilihan_d' =>
                    $gambarD,

                'jawaban' =>
                    $jawaban,

                'poin' =>
                    10,
            ]);
        }


        return response()->json([
            'message' =>
                'Kuis berhasil diperbarui',
        ]);
    }


    // =====================================================
    // DELETE IMAGE
    // =====================================================

    private function deleteImage($path)
    {
        if (
            $path &&
            Storage::disk('public')
                ->exists($path)
        ) {

            Storage::disk('public')
                ->delete($path);
        }
    }


    // =====================================================
    // DELETE SEMUA GAMBAR DETAIL
    // =====================================================

    private function deleteDetailImages(
        $detail
    ) {

        $fields = [
            'gambar_pertanyaan',
            'gambar_pilihan_a',
            'gambar_pilihan_b',
            'gambar_pilihan_c',
            'gambar_pilihan_d',
        ];

        foreach (
            $fields
            as $field
        ) {

            if (
                $detail->$field
            ) {

                $this->deleteImage(
                    $detail->$field
                );
            }
        }
    }


    // =====================================================
    // DELETE KUIS
    // =====================================================

    public function destroy($id)
    {
        $kuis =
            Kuis::findOrFail($id);

        $detail =
            DetailKuis::where(
                'id_kuis',
                $id
            )->get();

        foreach (
            $detail
            as $item
        ) {

            $this->deleteDetailImages(
                $item
            );
        }

        DetailKuis::where(
            'id_kuis',
            $id
        )->delete();

        $kuis->delete();

        return response()->json([
            'message' =>
                'Kuis berhasil dihapus',
        ]);
    }


    // =====================================================
    // UPDATE JUMLAH SOAL
    // =====================================================

    public function updateJumlahSoal(
        Request $request,
        $idKuis
    ) {

        $totalSoal =
            DetailKuis::where(
                'id_kuis',
                $idKuis
            )->count();

        $request->validate([
            'jml_soal' => [
                'required',
                'integer',
                'min:1',
                'max:' . $totalSoal,
            ],
        ]);

        $setting =
            JumlahSoal::first();

        if (!$setting) {
            $setting =
                new JumlahSoal();
        }

        $setting->jml_soal =
            $request->jml_soal;

        $setting->save();

        return response()->json([
            'message' =>
                'Jumlah soal berhasil diperbarui',

            'jml_soal' =>
                $setting->jml_soal,
        ]);
    }


    // =====================================================
    // SUBMIT KUIS SISWA
    // =====================================================

    public function submitQuiz(
        Request $request
    ) {

        $request->validate([
            'id_siswa' =>
                'required',

            'id_kuis' =>
                'required',

            'jawaban' =>
                'required|array',
        ]);


        $jumlahBenar =
            0;

        $jumlahSoal =
            count(
                $request->jawaban
            );

        $detailTerakhir =
            null;


        foreach (
            $request->jawaban
            as $item
        ) {

            $detail =
                DetailKuis::find(
                    $item['id_detail_kuis']
                );

            if (!$detail) {
                continue;
            }

            $detailTerakhir =
                $detail;

            $point =
                0;


            /*
            |--------------------------------------------------------------------------
            | NORMALISASI JAWABAN SISWA
            |--------------------------------------------------------------------------
            */

            $jawabanSiswa =
                $this->normalizeAnswer(
                    $item['jawaban_siswa']
                    ?? null
                );


            /*
            |--------------------------------------------------------------------------
            | CEK BENAR
            |--------------------------------------------------------------------------
            */

            if (
                $jawabanSiswa &&
                $jawabanSiswa ===
                $detail->jawaban
            ) {

                $jumlahBenar++;

                $point = 10;
            }


            /*
            |--------------------------------------------------------------------------
            | SIMPAN JAWABAN SISWA
            |--------------------------------------------------------------------------
            */

            JawabanSiswa::create([

                'id_detail_kuis' =>
                    $detail->id_detail_kuis,

                'id_siswa' =>
                    $request->id_siswa,

                'jawaban_siswa' =>
                    $jawabanSiswa,

                'perolehan_point' =>
                    $point,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | HITUNG NILAI
        |--------------------------------------------------------------------------
        */

        $nilai =
            0;

        if (
            $jumlahSoal > 0
        ) {

            $nilai =
                round(
                    (
                        $jumlahBenar /
                        $jumlahSoal
                    ) * 100,
                    2
                );
        }


        /*
        |--------------------------------------------------------------------------
        | JAWABAN TERAKHIR
        |--------------------------------------------------------------------------
        */

        $jawabanTerakhir =
            JawabanSiswa::where(
                'id_siswa',
                $request->id_siswa
            )
            ->latest()
            ->first();


        /*
        |--------------------------------------------------------------------------
        | SIMPAN NILAI
        |--------------------------------------------------------------------------
        */

        if (
            $detailTerakhir &&
            $jawabanTerakhir
        ) {

            PerolehanNilai::create([

                'id_siswa' =>
                    $request->id_siswa,

                'id_kuis' =>
                    $request->id_kuis,

                'id_jawaban_siswa' =>
                    $jawabanTerakhir
                        ->id_jawaban_siswa,

                'total_nilai' =>
                    $nilai,
            ]);
        }


        return response()->json([

            'message' =>
                'Kuis selesai',

            'nilai' =>
                $nilai,

            'jumlah_benar' =>
                $jumlahBenar,

            'jumlah_soal' =>
                $jumlahSoal,
        ]);
    }
}
