<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanFile extends Model
{
    protected $fillable = [
        'nama_file',
        'path_file',
        'file_hash',
        'jenis_laporan',
        'tanggal_laporan',
        'periode',
        'uploaded_by',
    ];

    public function pelanggarans()
    {
        return $this->hasMany(Pelanggaran::class);
    }
}