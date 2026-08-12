<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Hasil Penilaian</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin-top: 5px;
            font-size: 11px;
            color: #666;
        }

        .info {
            margin-bottom: 15px;
        }

        .info table {
            width: auto;
        }

        .info td {
            border: none;
            padding: 3px 10px 3px 0;
        }

        table.hasil {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.hasil th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        table.hasil td {
            border: 1px solid #cbd5e1;
            padding: 8px;
        }

        .nilai {
            text-align: center;
            font-weight: bold;
        }

        .status {
            text-align: center;
            font-weight: bold;
        }

        .memenuhi {
            color: #047857;
        }

        .tidak-memenuhi {
            color: #b45309;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>HASIL PENILAIAN KUIS SISWA</h2>

        <p>
            Laporan hasil penilaian kuis
        </p>
    </div>

    <div class="info">

        <table>
            <tr>
                <td><strong>KKM</strong></td>
                <td>: 75</td>
            </tr>

            <tr>
                <td><strong>Jumlah Data</strong></td>
                <td>: {{ $hasil->count() }}</td>
            </tr>

            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: {{ now()->format('d-m-Y') }}</td>
            </tr>
        </table>

    </div>

    <table class="hasil">

        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="25%">Siswa</th>
                <th width="30%">Kuis</th>
                <th width="15%">Nilai</th>
                <th width="15%">Tanggal</th>
                <th width="20%">Status</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($hasil as $index => $item)

                @php
                    $nilai = (float) ($item->total_nilai ?? 0);
                    $memenuhiKKM = $nilai >= 75;
                @endphp

                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $item->siswa->nama ?? '-' }}
                    </td>

                    <td>
                        {{ $item->kuis->judul ?? '-' }}
                    </td>

                    <td class="nilai">
                        {{ $nilai }}
                    </td>

                    <td>
                        {{ $item->created_at
                            ? $item->created_at->format('Y-m-d')
                            : '-' }}
                    </td>

                    <td class="status">

                        @if ($memenuhiKKM)

                            <span class="memenuhi">
                                Memenuhi KKM
                            </span>

                        @else

                            <span class="tidak-memenuhi">
                                Tidak Memenuhi KKM
                            </span>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="6"
                        style="text-align: center;"
                    >
                        Tidak ada data hasil penilaian.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

    <div class="footer">

        <p>
            Keterangan:
            Nilai ≥ 75 dinyatakan Memenuhi KKM.
        </p>

    </div>

</body>

</html>
