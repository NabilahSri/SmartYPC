<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiHarianSiswa extends Model
{
    //
    protected $guarded = [];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'nisn', 'nisn');
    }

    public function tahunajaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'idtahunajaran');
    }
}
