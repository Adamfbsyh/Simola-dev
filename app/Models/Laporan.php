<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    protected $fillable = [
        'tanggal',
        'periode',
        'unit',
        'kategori',
        'target',
        'realisasi',
        'status',
        'kendala',
        'keterangan',
        'uploaded_by',
    ];
}