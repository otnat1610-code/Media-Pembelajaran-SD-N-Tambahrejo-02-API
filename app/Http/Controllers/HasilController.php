<?php

namespace App\Http\Controllers;

use App\Models\PerolehanNilai;
use App\Exports\HasilExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class HasilController extends Controller
{
    // =========================
    // GET HASIL NILAI
    // =========================
    public function index()
    {
        $hasil = PerolehanNilai::with([
            'siswa',
            'kuis'
        ])
        ->latest()
        ->get();

        $formatted = [];

        foreach ($hasil as $item) {

            $nilai = (float) ($item->total_nilai ?? 0);

            $formatted[] = [

                'id' =>
                    $item->id_perolehan_nilai,

                'siswa' =>
                    $item->siswa->nama ?? '-',

                'kuis' =>
                    $item->kuis->judul ?? '-',

                'nilai' =>
                    $nilai,

                'tanggal' =>
                    $item->created_at
                        ? $item->created_at->format('Y-m-d')
                        : '-',

                'status' =>
                    $nilai >= 75
                        ? 'memenuhi kkm'
                        : 'tidak memenuhi kkm',
            ];
        }

        return response()->json($formatted);
    }


    // =========================
    // EXPORT EXCEL
    // =========================
    public function exportExcel()
    {
        return Excel::download(
            new HasilExport(),
            'hasil_penilaian.xlsx'
        );
    }


    // =========================
    // EXPORT PDF
    // =========================
    public function exportPdf()
    {
        try {

            $hasil = PerolehanNilai::with([
                'siswa',
                'kuis'
            ])
            ->latest()
            ->get();

            $pdf = Pdf::loadView(
                'exports.hasil-pdf',
                [
                    'hasil' => $hasil
                ]
            );

            $pdf->setPaper('A4', 'landscape');

            return $pdf->download(
                'hasil_penilaian.pdf'
            );

        } catch (\Throwable $e) {

            \Log::error(
                'EXPORT PDF HASIL GAGAL: ' .
                $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
