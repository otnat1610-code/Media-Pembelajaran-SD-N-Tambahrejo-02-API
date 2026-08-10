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
    // URL GAMBAR
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
    // FORMAT SOAL
    // =====================================================
    private function formatDetail($detail)
    {
        return [
            'id_detail_kuis' => $detail->id_detail_kuis,

            'q' => $detail->pertanyaan,

            'gambar_pertanyaan' => $this->imageUrl(
                $detail->gambar_pertanyaan
            ),

            'options' => [
                [
                    'text' => $detail->pilihan_a,
                    'image' => $this->imageUrl(
                        $detail->gambar_pilihan_a
                    ),
                    'value' => 'A',
                ],
                [
                    'text' => $detail->pilihan_b,
                    'image' => $this->imageUrl(
                        $detail->gambar_pilihan_b
                    ),
                    'value' => 'B',
                ],
                [
                    'text' => $detail->pilihan_c,
                    'image' => $this->imageUrl(
                        $detail->gambar_pilihan_c
                    ),
                    'value' => 'C',
                ],
                [
                    'text' => $detail->pilihan_d,
                    'image' => $this->imageUrl(
                        $detail->gambar_pilihan_d
                    ),
                    'value' => 'D',
                ],
            ],

            'answer' => $detail->jawaban,

            'poin' => $detail->poin,
        ];
    }

    // =====================================================
    // KUIS SISWA
    // =====================================================
    public function index()
    {
        $kuis = Kuis::with('detailKuis')
            ->where('status', 'aktif')
            ->get();

        $formatted = [];

        foreach ($kuis as $item) {
            foreach ($item->detailKuis as $detail) {

                $data = $this->formatDetail($detail);

                $data['judul_kuis'] = $item->judul;
                $data['id_kuis'] = $item->id_kuis;

                $formatted[] = $data;
            }
        }

        return response()->json($formatted);
    }

    // =====================================================
    // SOAL BERDASARKAN KUIS
    // =====================================================
    public function soalByKuis($id)
    {
        $setting = JumlahSoal::first();

        $jmlSoal = $setting
            ? $setting->jml_soal
            : null;

        $query = DetailKuis::where(
            'id_kuis',
            $id
        )->inRandomOrder();

        if ($jmlSoal) {
            $query->limit($jmlSoal);
        }

        $soal = $query->get();

        $formatted = $soal->map(function ($item) {
            return $this->formatDetail($item);
        });

        return response()->json(
            $formatted->values()
        );
    }

    // =====================================================
    // LIST KUIS
    // =====================================================
    public function listKuis()
    {
        return Kuis::select(
            'id_kuis',
            'judul',
            'deskripsi'
        )
            ->where('status', 'aktif')
            ->get();
    }

    // =====================================================
    // DATA ADMIN
    // =====================================================
    public function adminIndex()
    {
        $kuis = Kuis::with('detailKuis')
            ->orderBy('id_kuis', 'desc')
            ->get();

        $kuis->each(function ($item) {

            $item->detailKuis->each(function ($detail) {

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
            });
        });

        return response()->json($kuis);
    }

    // =====================================================
    // SIMPAN GAMBAR
    // =====================================================
    private function storeImage($file)
    {
        if (!$file) {
            return null;
        }

        return $file->store('kuis', 'public');
    }

    // =====================================================
    // VALIDASI SOAL
    // =====================================================
    private function validateQuestionContent(
        Request $request,
        $index,
        $soal
    ) {
        $pertanyaan = trim(
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

        foreach (['A', 'B', 'C', 'D'] as $option) {

            $text = trim(
                $soal['pilihan'][$option] ?? ''
            );

            $hasImage =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.$option"
                );

            if (
                $text === '' &&
                !$hasImage
            ) {
                return "Pilihan $option harus diisi atau diberikan gambar.";
            }
        }

        return null;
    }

    // =====================================================
    // TAMBAH KUIS
    // =====================================================
    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',

            'status' => 'required|in:aktif,draft',

            'soal' => 'required|array|min:5',

            'soal.*.pertanyaan' => 'nullable|string',

            'soal.*.pilihan.A' => 'nullable|string',
            'soal.*.pilihan.B' => 'nullable|string',
            'soal.*.pilihan.C' => 'nullable|string',
            'soal.*.pilihan.D' => 'nullable|string',

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
        foreach ($request->soal as $index => $soal) {

            $error = $this->validateQuestionContent(
                $request,
                $index,
                $soal
            );

            if ($error) {
                return response()->json([
                    'message' => $error,
                ], 422);
            }
        }

        // =================================================
        // BUAT KUIS
        // =================================================
        $kuis = Kuis::create([
            'id_guru' => 1,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'status' => $request->status,
            'total_soal' => count($request->soal),
        ]);

        // =================================================
        // SIMPAN SOAL
        // =================================================
        foreach ($request->soal as $index => $s) {

            $gambarPertanyaan =
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pertanyaan"
                    )
                )
                : null;

            $gambarA =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.A"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.A"
                    )
                )
                : null;

            $gambarB =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.B"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.B"
                    )
                )
                : null;

            $gambarC =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.C"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.C"
                    )
                )
                : null;

            $gambarD =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.D"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.D"
                    )
                )
                : null;

            DetailKuis::create([
                'id_kuis' => $kuis->id_kuis,

                'pertanyaan' =>
                    $s['pertanyaan'] ?? '',

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A'] ?? '',

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B'] ?? '',

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C'] ?? '',

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D'] ?? '',

                'gambar_pilihan_d' =>
                    $gambarD,

                'jawaban' =>
                    $s['jawaban'],

                'poin' => 10,
            ]);
        }

        return response()->json([
            'message' => 'Kuis berhasil ditambahkan',
            'data' => $kuis,
        ], 201);
    }

    // =====================================================
    // DETAIL KUIS
    // =====================================================
    public function show($id)
    {
        $kuis = Kuis::with(
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

        return response()->json($kuis);
    }

    // =====================================================
    // UPDATE KUIS
    // =====================================================
    public function update(Request $request, $id)
    {
        $kuis = Kuis::findOrFail($id);

        $request->validate([
            'judul' => 'required|string',

            'status' => 'required|in:aktif,draft',

            'soal' => 'required|array|min:5',

            'soal.*.pertanyaan' => 'nullable|string',

            'soal.*.pilihan.A' => 'nullable|string',
            'soal.*.pilihan.B' => 'nullable|string',
            'soal.*.pilihan.C' => 'nullable|string',
            'soal.*.pilihan.D' => 'nullable|string',

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
        // VALIDASI EDIT
        // =================================================
        foreach ($request->soal as $index => $s) {

            $idDetail =
                $s['id_detail_kuis'] ?? null;

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

            $pertanyaan = trim(
                $s['pertanyaan'] ?? ''
            );

            $hasQuestionImage =
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                );

            $oldQuestionImage =
                $detailLama &&
                $detailLama->gambar_pertanyaan &&
                empty(
                    $s['hapus_gambar_pertanyaan']
                );

            if (
                $pertanyaan === '' &&
                !$hasQuestionImage &&
                !$oldQuestionImage
            ) {
                return response()->json([
                    'message' =>
                        'Pertanyaan harus diisi atau diberikan gambar.',
                ], 422);
            }

            foreach (
                ['A', 'B', 'C', 'D']
                as $option
            ) {

                $text = trim(
                    $s['pilihan'][$option] ?? ''
                );

                $hasImage =
                    $request->hasFile(
                        "soal.$index.gambar_pilihan.$option"
                    );

                $field =
                    'gambar_pilihan_' .
                    strtolower($option);

                $oldImage =
                    $detailLama &&
                    $detailLama->$field &&
                    empty(
                        $s['hapus_gambar_pilihan'][$option]
                        ?? false
                    );

                if (
                    $text === '' &&
                    !$hasImage &&
                    !$oldImage
                ) {
                    return response()->json([
                        'message' =>
                            "Pilihan $option harus diisi atau diberikan gambar.",
                    ], 422);
                }
            }
        }

        // =================================================
        // UPDATE KUIS
        // =================================================
        $kuis->update([
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'status' => $request->status,
            'total_soal' => count($request->soal),
        ]);

        // =================================================
        // ID SOAL
        // =================================================
        $submittedIds = collect(
            $request->soal
        )
            ->pluck('id_detail_kuis')
            ->filter()
            ->map(fn ($id) => (int) $id)
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

        foreach ($soalLama as $lama) {

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

        // =================================================
        // UPDATE / TAMBAH SOAL
        // =================================================
        foreach ($request->soal as $index => $s) {

            $idDetail =
                $s['id_detail_kuis'] ?? null;

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

                // -----------------------------
                // PERTANYAAN
                // -----------------------------
                $gambarPertanyaan =
                    $detail->gambar_pertanyaan;

                if (
                    !empty(
                        $s['hapus_gambar_pertanyaan']
                    )
                ) {

                    $this->deleteImage(
                        $gambarPertanyaan
                    );

                    $gambarPertanyaan = null;
                }

                if (
                    $request->hasFile(
                        "soal.$index.gambar_pertanyaan"
                    )
                ) {

                    $this->deleteImage(
                        $gambarPertanyaan
                    );

                    $gambarPertanyaan =
                        $this->storeImage(
                            $request->file(
                                "soal.$index.gambar_pertanyaan"
                            )
                        );
                }

                // -----------------------------
                // PILIHAN
                // -----------------------------
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

                    // HAPUS
                    if (
                        !empty(
                            $s[
                                'hapus_gambar_pilihan'
                            ][$option]
                            ?? false
                        )
                    ) {

                        $this->deleteImage(
                            $gambarPilihan[$option]
                        );

                        $gambarPilihan[$option] =
                            null;
                    }

                    // GANTI
                    if (
                        $request->hasFile(
                            "soal.$index.gambar_pilihan.$option"
                        )
                    ) {

                        $this->deleteImage(
                            $gambarPilihan[$option]
                        );

                        $gambarPilihan[$option] =
                            $this->storeImage(
                                $request->file(
                                    "soal.$index.gambar_pilihan.$option"
                                )
                            );
                    }
                }

                // -----------------------------
                // UPDATE
                // -----------------------------
                $detail->update([

                    'pertanyaan' =>
                        $s['pertanyaan'] ?? '',

                    'gambar_pertanyaan' =>
                        $gambarPertanyaan,

                    'pilihan_a' =>
                        $s['pilihan']['A'] ?? '',

                    'gambar_pilihan_a' =>
                        $gambarPilihan['A'],

                    'pilihan_b' =>
                        $s['pilihan']['B'] ?? '',

                    'gambar_pilihan_b' =>
                        $gambarPilihan['B'],

                    'pilihan_c' =>
                        $s['pilihan']['C'] ?? '',

                    'gambar_pilihan_c' =>
                        $gambarPilihan['C'],

                    'pilihan_d' =>
                        $s['pilihan']['D'] ?? '',

                    'gambar_pilihan_d' =>
                        $gambarPilihan['D'],

                    'jawaban' =>
                        $s['jawaban'],

                    'poin' => 10,
                ]);

                continue;
            }

            // =================================================
            // SOAL BARU
            // =================================================
            $gambarPertanyaan =
                $request->hasFile(
                    "soal.$index.gambar_pertanyaan"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pertanyaan"
                    )
                )
                : null;

            $gambarA =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.A"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.A"
                    )
                )
                : null;

            $gambarB =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.B"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.B"
                    )
                )
                : null;

            $gambarC =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.C"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.C"
                    )
                )
                : null;

            $gambarD =
                $request->hasFile(
                    "soal.$index.gambar_pilihan.D"
                )
                ? $this->storeImage(
                    $request->file(
                        "soal.$index.gambar_pilihan.D"
                    )
                )
                : null;

            DetailKuis::create([

                'id_kuis' => $id,

                'pertanyaan' =>
                    $s['pertanyaan'] ?? '',

                'gambar_pertanyaan' =>
                    $gambarPertanyaan,

                'pilihan_a' =>
                    $s['pilihan']['A'] ?? '',

                'gambar_pilihan_a' =>
                    $gambarA,

                'pilihan_b' =>
                    $s['pilihan']['B'] ?? '',

                'gambar_pilihan_b' =>
                    $gambarB,

                'pilihan_c' =>
                    $s['pilihan']['C'] ?? '',

                'gambar_pilihan_c' =>
                    $gambarC,

                'pilihan_d' =>
                    $s['pilihan']['D'] ?? '',

                'gambar_pilihan_d' =>
                    $gambarD,

                'jawaban' =>
                    $s['jawaban'],

                'poin' => 10,
            ]);
        }

        return response()->json([
            'message' =>
                'Kuis berhasil diperbarui',
        ]);
    }

    // =====================================================
    // HAPUS GAMBAR
    // =====================================================
    private function deleteImage($path)
    {
        if (
            $path &&
            Storage::disk('public')->exists($path)
        ) {
            Storage::disk('public')->delete($path);
        }
    }

    // =====================================================
    // HAPUS SEMUA GAMBAR
    // =====================================================
    private function deleteDetailImages($detail)
    {
        $fields = [
            'gambar_pertanyaan',
            'gambar_pilihan_a',
            'gambar_pilihan_b',
            'gambar_pilihan_c',
            'gambar_pilihan_d',
        ];

        foreach ($fields as $field) {

            if ($detail->$field) {
                $this->deleteImage(
                    $detail->$field
                );
            }
        }
    }

    // =====================================================
    // HAPUS KUIS
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

        foreach ($detail as $item) {

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
    // JUMLAH SOAL
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
    // SUBMIT KUIS
    // =====================================================
    public function submitQuiz(
        Request $request
    ) {
        $request->validate([
            'id_siswa' => 'required',
            'id_kuis' => 'required',
            'jawaban' => 'required|array',
        ]);

        $jumlahBenar = 0;

        $jumlahSoal =
            count($request->jawaban);

        $detailTerakhir = null;

        foreach (
            $request->jawaban as $item
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

            /*
             * Jawaban siswa tetap berupa A/B/C/D.
             * Tidak peduli pilihan tersebut berupa teks,
             * gambar, atau teks + gambar.
             */
            if (
                strtoupper(
                    $item['jawaban_siswa']
                ) ===
                strtoupper(
                    $detail->jawaban
                )
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

        $nilai = 0;

        if ($jumlahSoal > 0) {

            $nilai = round(
                (
                    $jumlahBenar /
                    $jumlahSoal
                ) * 100,
                2
            );
        }

        $jawabanTerakhir =
            JawabanSiswa::where(
                'id_siswa',
                $request->id_siswa
            )
            ->latest()
            ->first();

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
