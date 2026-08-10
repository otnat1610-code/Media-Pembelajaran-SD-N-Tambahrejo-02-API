<?php

namespace App\Http\Controllers;

use App\Models\Kuis;
use App\Models\DetailKuis;
use App\Models\JumlahSoal;
use App\Models\JawabanSiswa;
use App\Models\PerolehanNilai;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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

        // Jika sudah URL lengkap
        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        return asset(
            'storage/' . ltrim($path, '/')
        );
    }


    // =====================================================
    // FORMAT DETAIL SOAL UNTUK SISWA
    // =====================================================

    private function formatDetail($detail)
    {
        return [
            'id_detail_kuis' =>
                $detail->id_detail_kuis,

            'q' =>
                $detail->pertanyaan,

            'gambar_pertanyaan' =>
                $this->imageUrl(
                    $detail->gambar_pertanyaan
                ),

            'options' => [

                [
                    'text' =>
                        $detail->pilihan_a,

                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_a
                        ),
                ],

                [
                    'text' =>
                        $detail->pilihan_b,

                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_b
                        ),
                ],

                [
                    'text' =>
                        $detail->pilihan_c,

                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_c
                        ),
                ],

                [
                    'text' =>
                        $detail->pilihan_d,

                    'image' =>
                        $this->imageUrl(
                            $detail->gambar_pilihan_d
                        ),
                ],
            ],

            'answer' =>
                $detail->jawaban,

            'poin' =>
                $detail->poin,
        ];
    }


    // =====================================================
    // DATA KUIS SISWA
    // =====================================================

    public function index()
    {
        $kuis = Kuis::with('detailKuis')
            ->where('status', 'aktif')
            ->get();

        $formatted = [];

        foreach ($kuis as $item) {

            foreach ($item->detailKuis as $detail) {

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
            )->inRandomOrder();

        if ($jmlSoal) {
            $query->limit(
                $jmlSoal
            );
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
            Kuis::with(
                'detailKuis'
            )
            ->orderBy(
                'id_kuis',
                'desc'
            )
            ->get();

        $kuis->each(
            function ($item) {

                $item->detailKuis->each(
                    function ($detail) {

                        // Simpan PATH asli
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


                        // Ubah menjadi URL
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
        if (
            !$file ||
            !($file instanceof UploadedFile)
        ) {
            return null;
        }

        return $file->store(
            'kuis',
            'public'
        );
    }


    // =====================================================
    // CEK APAKAH ADA FILE GAMBAR
    // =====================================================

    private function hasUploadedImage(
        Request $request,
        $field
    ) {
        $file =
            $request->file($field);

        return
            $file instanceof UploadedFile;
    }


    // =====================================================
    // VALIDASI ISI SOAL
    //
    // TEKS BOLEH KOSONG JIKA ADA GAMBAR
    // =====================================================

    private function validateQuestionContent(
        Request $request,
        $index,
        $soal
    ) {

        // =================================================
        // PERTANYAAN
        // =================================================

        $pertanyaan =
            trim(
                $soal['pertanyaan']
                ?? ''
            );

        $adaGambarPertanyaan =
            $this->hasUploadedImage(
                $request,
                "soal.$index.gambar_pertanyaan"
            );

        if (
            $pertanyaan === '' &&
            !$adaGambarPertanyaan
        ) {

            return
                'Pertanyaan harus diisi atau diberikan gambar.';
        }


        // =================================================
        // PILIHAN A-D
        // =================================================

        foreach (
            ['A', 'B', 'C', 'D']
            as $option
        ) {

            $text =
                trim(
                    $soal['pilihan'][$option]
                    ?? ''
                );

            $adaGambar =
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.$option"
                );

            if (
                $text === '' &&
                !$adaGambar
            ) {

                return
                    "Pilihan $option harus diisi atau diberikan gambar.";
            }
        }

        return null;
    }


    // =====================================================
    // VALIDASI SOAL UNTUK EDIT
    // =====================================================

    private function validateQuestionContentUpdate(
        Request $request,
        $index,
        $soal,
        $detailLama
    ) {

        // =================================================
        // PERTANYAAN
        // =================================================

        $pertanyaan =
            trim(
                $soal['pertanyaan']
                ?? ''
            );

        $adaGambarBaru =
            $this->hasUploadedImage(
                $request,
                "soal.$index.gambar_pertanyaan"
            );

        $adaGambarLama =
            $detailLama &&
            !empty(
                $detailLama->gambar_pertanyaan
            ) &&
            empty(
                $soal['hapus_gambar_pertanyaan']
                ?? false
            );

        if (
            $pertanyaan === '' &&
            !$adaGambarBaru &&
            !$adaGambarLama
        ) {

            return
                'Pertanyaan harus diisi atau diberikan gambar.';
        }


        // =================================================
        // PILIHAN A-D
        // =================================================

        foreach (
            ['A', 'B', 'C', 'D']
            as $option
        ) {

            $text =
                trim(
                    $soal['pilihan'][$option]
                    ?? ''
                );

            $adaGambarBaru =
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.$option"
                );

            $field =
                'gambar_pilihan_' .
                strtolower($option);

            $hapusGambar =
                $soal[
                    'hapus_gambar_pilihan'
                ][$option]
                ?? false;

            $adaGambarLama =
                $detailLama &&
                !empty(
                    $detailLama->$field
                ) &&
                !$hapusGambar;

            if (
                $text === '' &&
                !$adaGambarBaru &&
                !$adaGambarLama
            ) {

                return
                    "Pilihan $option harus diisi atau diberikan gambar.";
            }
        }

        return null;
    }


    // =====================================================
    // TAMBAH KUIS
    // =====================================================

    public function store(
        Request $request
    ) {

        // =================================================
        // VALIDASI DASAR
        // =================================================

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

            'soal.*.jawaban' =>
                'required|in:A,B,C,D',

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


        // =================================================
        // VALIDASI TEKS / GAMBAR
        // =================================================

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
                ], 422);
            }
        }


        // =================================================
        // BUAT KUIS
        // =================================================

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
                    count(
                        $request->soal
                    ),
            ]);


        // =================================================
        // SIMPAN SOAL
        // =================================================

        foreach (
            $request->soal
            as $index => $s
        ) {

            // =============================================
            // GAMBAR PERTANYAAN
            // =============================================

            $gambarPertanyaan = null;

            if (
                $this->hasUploadedImage(
                    $request,
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


            // =============================================
            // GAMBAR PILIHAN A
            // =============================================

            $gambarA = null;

            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.A"
                )
            ) {

                $gambarA =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.A"
                        )
                    );
            }


            // =============================================
            // GAMBAR PILIHAN B
            // =============================================

            $gambarB = null;

            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.B"
                )
            ) {

                $gambarB =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.B"
                        )
                    );
            }


            // =============================================
            // GAMBAR PILIHAN C
            // =============================================

            $gambarC = null;

            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.C"
                )
            ) {

                $gambarC =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.C"
                        )
                    );
            }


            // =============================================
            // GAMBAR PILIHAN D
            // =============================================

            $gambarD = null;

            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.D"
                )
            ) {

                $gambarD =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.D"
                        )
                    );
            }


            // =============================================
            // SIMPAN DETAIL SOAL
            // =============================================

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

                'jawaban' =>
                    $s['jawaban'],

                'poin' =>
                    10,
            ]);
        }


        // =================================================
        // RESPONSE
        // =================================================

        return response()->json([

            'message' =>
                'Kuis berhasil ditambahkan',

            'data' =>
                $kuis,

        ], 201);
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


        // =================================================
        // VALIDASI DASAR
        // =================================================

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

            'soal.*.jawaban' =>
                'required|in:A,B,C,D',

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


        // =================================================
        // VALIDASI SETIAP SOAL
        // =================================================

        foreach (
            $request->soal
            as $index => $soal
        ) {

            $idDetail =
                $soal[
                    'id_detail_kuis'
                ]
                ?? null;


            $detailLama =
                $idDetail
                    ? DetailKuis::where(
                        'id_detail_kuis',
                        $idDetail
                    )
                    ->where(
                        'id_kuis',
                        $id
                    )
                    ->first()
                    : null;


            $error =
                $this->validateQuestionContentUpdate(
                    $request,
                    $index,
                    $soal,
                    $detailLama
                );


            if ($error) {

                return response()->json([
                    'message' =>
                        $error,
                ], 422);
            }
        }


        // =================================================
        // UPDATE DATA KUIS
        // =================================================

        $kuis->update([

            'judul' =>
                $request->judul,

            'deskripsi' =>
                $request->deskripsi,

            'status' =>
                $request->status,

            'total_soal' =>
                count(
                    $request->soal
                ),
        ]);


        // =================================================
        // ID SOAL YANG MASIH DIGUNAKAN
        // =================================================

        $submittedIds =
            collect(
                $request->soal
            )
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


        // =================================================
        // HAPUS SOAL YANG DIHILANGKAN
        // =================================================

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
                    (int)
                    $lama->id_detail_kuis,
                    $submittedIds
                )
            ) {

                $this->deleteDetailImages(
                    $lama
                );

                $lama->delete();
            }
        }


        // =================================================
        // UPDATE / TAMBAH SOAL
        // =================================================

        foreach (
            $request->soal
            as $index => $s
        ) {

            $idDetail =
                $s[
                    'id_detail_kuis'
                ]
                ?? null;


            // =================================================
            // SOAL LAMA
            // =================================================

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


                // =============================================
                // GAMBAR PERTANYAAN
                // =============================================

                $gambarPertanyaan =
                    $detail->gambar_pertanyaan;


                // HAPUS GAMBAR LAMA
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


                // GANTI / TAMBAH GAMBAR
                if (
                    $this->hasUploadedImage(
                        $request,
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


                // =============================================
                // GAMBAR PILIHAN A-D
                // =============================================

                $gambarPilihan = [];


                foreach (
                    ['A', 'B', 'C', 'D']
                    as $option
                ) {

                    $field =
                        'gambar_pilihan_' .
                        strtolower(
                            $option
                        );


                    $gambarPilihan[$option] =
                        $detail->$field;


                    // =========================================
                    // HAPUS GAMBAR
                    // =========================================

                    $hapus =
                        $s[
                            'hapus_gambar_pilihan'
                        ][$option]
                        ?? false;


                    if ($hapus) {

                        if (
                            $gambarPilihan[
                                $option
                            ]
                        ) {

                            $this->deleteImage(
                                $gambarPilihan[
                                    $option
                                ]
                            );
                        }


                        $gambarPilihan[
                            $option
                        ] = null;
                    }


                    // =========================================
                    // GANTI / TAMBAH GAMBAR
                    // =========================================

                    if (
                        $this->hasUploadedImage(
                            $request,
                            "soal.$index.gambar_pilihan.$option"
                        )
                    ) {

                        if (
                            $gambarPilihan[
                                $option
                            ]
                        ) {

                            $this->deleteImage(
                                $gambarPilihan[
                                    $option
                                ]
                            );
                        }


                        $gambarPilihan[
                            $option
                        ] =
                            $this->storeImage(
                                $request->file(
                                    "soal.$index.gambar_pilihan.$option"
                                )
                            );
                    }
                }


                // =============================================
                // UPDATE DETAIL SOAL
                // =============================================

                $detail->update([

                    'pertanyaan' =>
                        $s[
                            'pertanyaan'
                        ]
                        ?? '',

                    'gambar_pertanyaan' =>
                        $gambarPertanyaan,


                    'pilihan_a' =>
                        $s[
                            'pilihan'
                        ]['A']
                        ?? '',

                    'gambar_pilihan_a' =>
                        $gambarPilihan['A'],


                    'pilihan_b' =>
                        $s[
                            'pilihan'
                        ]['B']
                        ?? '',

                    'gambar_pilihan_b' =>
                        $gambarPilihan['B'],


                    'pilihan_c' =>
                        $s[
                            'pilihan'
                        ]['C']
                        ?? '',

                    'gambar_pilihan_c' =>
                        $gambarPilihan['C'],


                    'pilihan_d' =>
                        $s[
                            'pilihan'
                        ]['D']
                        ?? '',

                    'gambar_pilihan_d' =>
                        $gambarPilihan['D'],


                    'jawaban' =>
                        $s['jawaban'],

                    'poin' =>
                        10,
                ]);


                continue;
            }


            // =================================================
            // SOAL BARU SAAT EDIT
            // =================================================

            $gambarPertanyaan =
                null;

            if (
                $this->hasUploadedImage(
                    $request,
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


            $gambarA = null;
            $gambarB = null;
            $gambarC = null;
            $gambarD = null;


            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.A"
                )
            ) {

                $gambarA =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.A"
                        )
                    );
            }


            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.B"
                )
            ) {

                $gambarB =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.B"
                        )
                    );
            }


            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.C"
                )
            ) {

                $gambarC =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.C"
                        )
                    );
            }


            if (
                $this->hasUploadedImage(
                    $request,
                    "soal.$index.gambar_pilihan.D"
                )
            ) {

                $gambarD =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan.D"
                        )
                    );
            }


            // =============================================
            // SIMPAN SOAL BARU
            // =============================================

            DetailKuis::create([

                'id_kuis' =>
                    $id,

                'pertanyaan' =>
                    $s[
                        'pertanyaan'
                    ]
                    ?? '',

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,


                'pilihan_a' =>
                    $s[
                        'pilihan'
                    ]['A']
                    ?? '',

                'gambar_pilihan_a' =>
                    $gambarA,


                'pilihan_b' =>
                    $s[
                        'pilihan'
                    ]['B']
                    ?? '',

                'gambar_pilihan_b' =>
                    $gambarB,


                'pilihan_c' =>
                    $s[
                        'pilihan'
                    ]['C']
                    ?? '',

                'gambar_pilihan_c' =>
                    $gambarC,


                'pilihan_d' =>
                    $s[
                        'pilihan'
                    ]['D']
                    ?? '',

                'gambar_pilihan_d' =>
                    $gambarD,


                'jawaban' =>
                    $s['jawaban'],

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
    // HAPUS SATU GAMBAR
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
    // HAPUS SEMUA GAMBAR DETAIL
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
                    $item[
                        'id_detail_kuis'
                    ]
                );


            if (!$detail) {
                continue;
            }


            $detailTerakhir =
                $detail;


            $point =
                0;


            if (
                $item[
                    'jawaban_siswa'
                ]
                ==
                $detail->jawaban
            ) {

                $jumlahBenar++;

                $point =
                    10;
            }


            JawabanSiswa::create([

                'id_detail_kuis' =>
                    $detail->id_detail_kuis,

                'id_siswa' =>
                    $request->id_siswa,

                'jawaban_siswa' =>
                    $item[
                        'jawaban_siswa'
                    ],

                'perolehan_point' =>
                    $point,

            ]);
        }


        // =================================================
        // HITUNG NILAI
        // =================================================

        $nilai =
            0;


        if (
            $jumlahSoal > 0
        ) {

            $nilai =
                round(

                    (
                        $jumlahBenar
                        /
                        $jumlahSoal
                    )
                    * 100,

                    2
                );
        }


        // =================================================
        // JAWABAN TERAKHIR
        // =================================================

        $jawabanTerakhir =
            JawabanSiswa::where(
                'id_siswa',
                $request->id_siswa
            )
            ->latest()
            ->first();


        // =================================================
        // SIMPAN PEROLEHAN NILAI
        // =================================================

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


        // =================================================
        // RESPONSE
        // =================================================

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
