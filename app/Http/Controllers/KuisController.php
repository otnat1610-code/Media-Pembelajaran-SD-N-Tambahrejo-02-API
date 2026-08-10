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

        return asset('storage/' . $path);
    }


    // =====================================================
    // FORMAT DETAIL SOAL
    // Digunakan untuk kuis siswa
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
    // FORMAT DETAIL SOAL UNTUK ADMIN
    // =====================================================
    private function formatAdminDetail($detail)
    {
        return [

            'id_detail_kuis' =>
                $detail->id_detail_kuis,

            'id_kuis' =>
                $detail->id_kuis,

            'pertanyaan' =>
                $detail->pertanyaan,

            'gambar_pertanyaan' =>
                $this->imageUrl(
                    $detail->gambar_pertanyaan
                ),

            'pilihan_a' =>
                $detail->pilihan_a,

            'gambar_pilihan_a' =>
                $this->imageUrl(
                    $detail->gambar_pilihan_a
                ),

            'pilihan_b' =>
                $detail->pilihan_b,

            'gambar_pilihan_b' =>
                $this->imageUrl(
                    $detail->gambar_pilihan_b
                ),

            'pilihan_c' =>
                $detail->pilihan_c,

            'gambar_pilihan_c' =>
                $this->imageUrl(
                    $detail->gambar_pilihan_c
                ),

            'pilihan_d' =>
                $detail->pilihan_d,

            'gambar_pilihan_d' =>
                $this->imageUrl(
                    $detail->gambar_pilihan_d
                ),

            'jawaban' =>
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
                    $this->formatDetail($detail);

                $data['judul_kuis'] =
                    $item->judul;

                $formatted[] = $data;
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
            $soal->map(function ($item) {

                return $this->formatDetail(
                    $item
                );

            });

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

        /*
        |--------------------------------------------------------------------------
        | Ubah path gambar menjadi URL lengkap
        |--------------------------------------------------------------------------
        */
        $formatted =
            $kuis->map(function ($item) {

                $data =
                    $item->toArray();

                $data['detail_kuis'] =
                    $item->detailKuis
                        ->map(function ($detail) {

                            return $this->formatAdminDetail(
                                $detail
                            );

                        })
                        ->values()
                        ->toArray();

                return $data;
            });

        return response()->json(
            $formatted->values()
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
    public function store(Request $request)
    {
        $request->validate([

            'judul' =>
                'required',

            'status' =>
                'required',

            'soal' =>
                'required|array|min:1',

            'soal.*.pertanyaan' =>
                'required',

            'soal.*.pilihan.A' =>
                'required',

            'soal.*.pilihan.B' =>
                'required',

            'soal.*.pilihan.C' =>
                'required',

            'soal.*.pilihan.D' =>
                'required',

            'soal.*.jawaban' =>
                'required|in:A,B,C,D',

        ], [

            'soal.*.pertanyaan.required' =>
                'Pertanyaan tidak boleh kosong.',

            'soal.*.pilihan.A.required' =>
                'Pilihan A tidak boleh kosong.',

            'soal.*.pilihan.B.required' =>
                'Pilihan B tidak boleh kosong.',

            'soal.*.pilihan.C.required' =>
                'Pilihan C tidak boleh kosong.',

            'soal.*.pilihan.D.required' =>
                'Pilihan D tidak boleh kosong.',

            'soal.*.jawaban.required' =>
                'Jawaban benar harus dipilih.',

        ]);


        // =================================================
        // BUAT KUIS
        // =================================================
        $kuis = Kuis::create([

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


        // =================================================
        // SIMPAN SOAL
        // =================================================
        foreach (
            $request->soal
            as $index => $s
        ) {

            // ---------------------------------------------
            // GAMBAR SOAL
            // ---------------------------------------------
            $gambarPertanyaan = null;

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


            // ---------------------------------------------
            // GAMBAR A
            // ---------------------------------------------
            $gambarA = null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_a"
                )
            ) {

                $gambarA =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_a"
                        )
                    );
            }


            // ---------------------------------------------
            // GAMBAR B
            // ---------------------------------------------
            $gambarB = null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_b"
                )
            ) {

                $gambarB =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_b"
                        )
                    );
            }


            // ---------------------------------------------
            // GAMBAR C
            // ---------------------------------------------
            $gambarC = null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_c"
                )
            ) {

                $gambarC =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_c"
                        )
                    );
            }


            // ---------------------------------------------
            // GAMBAR D
            // ---------------------------------------------
            $gambarD = null;

            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_d"
                )
            ) {

                $gambarD =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_d"
                        )
                    );
            }


            // =================================================
            // SIMPAN DETAIL
            // =================================================
            DetailKuis::create([

                'id_kuis' =>
                    $kuis->id_kuis,

                'pertanyaan' =>
                    $s['pertanyaan'],

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A'],

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B'],

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C'],

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D'],

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

        $request->validate([

            'judul' =>
                'required',

            'status' =>
                'required',

            'soal' =>
                'required|array|min:1',

        ]);


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
                count($request->soal),

        ]);


        // =================================================
        // SOAL LAMA
        // =================================================
        $soalLama =
            DetailKuis::where(
                'id_kuis',
                $id
            )->get();


        // =================================================
        // HAPUS GAMBAR LAMA
        // =================================================
        foreach (
            $soalLama as $lama
        ) {

            $gambarFields = [

                'gambar_pertanyaan',
                'gambar_pilihan_a',
                'gambar_pilihan_b',
                'gambar_pilihan_c',
                'gambar_pilihan_d',

            ];

            foreach (
                $gambarFields as $field
            ) {

                if (
                    $lama->$field &&
                    Storage::disk('public')
                        ->exists(
                            $lama->$field
                        )
                ) {

                    Storage::disk('public')
                        ->delete(
                            $lama->$field
                        );
                }
            }
        }


        // =================================================
        // HAPUS DETAIL LAMA
        // =================================================
        DetailKuis::where(
            'id_kuis',
            $id
        )->delete();


        // =================================================
        // INSERT ULANG SOAL
        // =================================================
        foreach (
            $request->soal
            as $index => $s
        ) {

            $gambarPertanyaan = null;
            $gambarA = null;
            $gambarB = null;
            $gambarC = null;
            $gambarD = null;


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


            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_a"
                )
            ) {

                $gambarA =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_a"
                        )
                    );
            }


            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_b"
                )
            ) {

                $gambarB =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_b"
                        )
                    );
            }


            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_c"
                )
            ) {

                $gambarC =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_c"
                        )
                    );
            }


            if (
                $request->hasFile(
                    "soal.$index.gambar_pilihan_d"
                )
            ) {

                $gambarD =
                    $this->storeImage(
                        $request->file(
                            "soal.$index.gambar_pilihan_d"
                        )
                    );
            }


            // =================================================
            // INSERT
            // =================================================
            DetailKuis::create([

                'id_kuis' =>
                    $id,

                'pertanyaan' =>
                    $s['pertanyaan'],

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A'],

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B'],

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C'],

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D'],

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
                'Kuis berhasil diupdate',

        ]);
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
            $detail as $item
        ) {

            $fields = [

                'gambar_pertanyaan',
                'gambar_pilihan_a',
                'gambar_pilihan_b',
                'gambar_pilihan_c',
                'gambar_pilihan_d',

            ];

            foreach (
                $fields as $field
            ) {

                if (
                    $item->$field &&
                    Storage::disk('public')
                        ->exists(
                            $item->$field
                        )
                ) {

                    Storage::disk('public')
                        ->delete(
                            $item->$field
                        );
                }
            }
        }


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


        $jumlahBenar = 0;

        $jumlahSoal =
            count($request->jawaban);

        $detailTerakhir = null;


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

            $point = 0;


            if (
                $item['jawaban_siswa']
                ==
                $detail->jawaban
            ) {

                $jumlahBenar++;

                $point = 10;
            }


            JawabanSiswa::create([

                'id_detail_kuis' =>
                    $detail->id_detail_kuis,

                'id_siswa' =>
                    $request->id_siswa,

                'jawaban_siswa' =>
                    $item['jawaban_siswa'],

                'perolehan_point' =>
                    $point,

            ]);
        }


        // =================================================
        // NILAI
        // =================================================
        $nilai = 0;

        if ($jumlahSoal > 0) {

            $nilai =
                round(
                    (
                        $jumlahBenar
                        /
                        $jumlahSoal
                    ) * 100,
                    2
                );
        }


        // =================================================
        // AMBIL JAWABAN TERAKHIR
        // =================================================
        $jawabanTerakhir =
            JawabanSiswa::where(
                'id_siswa',
                $request->id_siswa
            )
            ->latest()
            ->first();


        // =================================================
        // SIMPAN NILAI
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
