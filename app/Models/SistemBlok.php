<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistemBlok extends Model
{
    //
    protected $guarded = [];

    public function tahunajaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'idtahunajaran');
    }

    public function jadwalmengajar()
    {
        return $this->hasMany(JadwalMengajar::class, 'idsistemblok');
    }

    public function jadwalsistemblok()
    {
        return $this->hasMany(JadwalSistemBlok::class, 'idsistemblok');
    }
}
