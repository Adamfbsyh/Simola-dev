<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportUpload extends Model
{
    protected $fillable = [
        'jenis_laporan',
        'periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'bulan',
        'tahun',
        'nama_file',
        'path_file',
        'file_hash',
        'total_data',
        'status',
        'catatan',
        'uploaded_by',
    ];

    public function events()
    {
        return $this->hasMany(MonitoringEvent::class);
    }
}