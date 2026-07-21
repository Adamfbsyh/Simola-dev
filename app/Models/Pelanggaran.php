<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggaran extends Model
{
    protected $fillable = [
        'laporan_file_id',
        'tanggal_laporan',
        'no_urut',
        'nopol',
        'terminal',
        'driver',
        'kategori_sanksi',
        'jenis_pelanggaran',
        'nilai',
        'evidence',
        'row_excel',
    ];

    public function laporanFile()
    {
        return $this->belongsTo(LaporanFile::class);
    }
}