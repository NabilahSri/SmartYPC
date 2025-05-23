<?php

namespace App\Http\Controllers;

use App\Models\DetailNilaiSiswa;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\Matpel;
use App\Models\MatpelPengampu;
use App\Models\NilaiSiswa;
use App\Models\PersentaseNilaiSiswa;
use App\Models\Rombel;
use App\Models\TahunAjaran;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class NilaiSiswaController extends Controller
{
    //
    protected $tahunajaran;
    public function __construct()
    {
        $this->tahunajaran = TahunAjaran::where('status', 1)->first();
    }
    public function index(Request $request)
    {
        $title = 'Data Pengolah Nilai!';
        $text = "Yakin ingin menghapus data ini?";
        confirmDelete($title, $text);

        $tahunajaran = $this->tahunajaran;

        $kelas = JadwalMengajar::with('kelas')->whereHas('sistemblok', function ($query) use ($tahunajaran) {
            $query->where([
                'idtahunajaran' => $tahunajaran->id,
                'semester' => $tahunajaran->semester,
            ]);
        })->where('nip', Auth::user()->staf->nip)->groupBy('idkelas')->get();

        $matpel = MatpelPengampu::with('matpel')->where([
            'idtahunajaran' => $tahunajaran->id
        ])->where('nip', Auth::user()->staf->nip)->groupBy('kode_matpel')->get();

        $nilaisiswa = NilaiSiswa::WithAvg('detailnilaisiswa', 'nilai')->where([
            'semester' => $tahunajaran->semester,
            'idtahunajaran' => $tahunajaran->id,
            'nip' => Auth::user()->staf->nip
        ])->get();

        $data['kelas'] = $kelas;
        $data['matpel'] = $matpel;
        $data['nilaisiswa'] = $nilaisiswa;
        $data['tugas'] = $nilaisiswa->where('kategori', 'tugas');
        $data['sumatif'] = $nilaisiswa->where('kategori', 'sumatif');
        $data['uts'] = $nilaisiswa->where('kategori', 'uts');
        $data['uas'] = $nilaisiswa->where('kategori', 'uas');
        $data['kategori'] = $request->kategori ?? 'tugas';
        $data['data_kategori'] = ['tugas', 'sumatif', 'uts', 'uas'];
        return view('pages.nilaisiswa.index', $data);
    }

    public function store(Request $request)
    {
        //
        $validate = $request->validate([
            'kategori' => 'required',
            'idkelas' => 'required',
            'kode_matpel' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'keterangan' => 'required'
        ]);

        $tahunajaran = $this->tahunajaran;
        $validate['semester'] = $tahunajaran->semester;
        $validate['idtahunajaran'] = $tahunajaran->id;
        $validate['nip'] = Auth::user()->staf->nip;

        NilaiSiswa::create($validate);

        return redirect()->route('nilai-siswa', [
            'kategori' => $request->kategori,
        ])->with('success', 'Data berhasil disimpan');
    }

    public function update(Request $request, String $id)
    {
        //
        try {
            $id = Crypt::decrypt($id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }
        $validate = $request->validate([
            'kategori' => 'required',
            'kode_matpel' => 'required',
            'tanggal_pelaksanaan' => 'required',
            'keterangan' => 'required'
        ]);

        $tahunajaran = $this->tahunajaran;
        $validate['semester'] = $tahunajaran->semester;
        $validate['idtahunajaran'] = $tahunajaran->id;
        $validate['nip'] = Auth::user()->staf->nip;

        NilaiSiswa::find($id)->update($validate);

        return redirect()->route('nilai-siswa', [
            'kategori' => $request->kategori,
        ])->with('success', 'Data berhasil diubah');
    }

    public function destroy(String $id)
    {
        //
        try {
            $id = Crypt::decrypt($id);
        } catch (DecryptException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }

        $nilaisiswa = NilaiSiswa::find($id)->delete();
        return redirect()->route('nilai-siswa', [
            'kategori' => $nilaisiswa->kategori,
        ])->with('success', 'Data berhasil dihapus');
    }

    public function inputNilai(String $kategori, String $id)
    {
        //
        try {
            $id = Crypt::decrypt($id);
        } catch (DecryptException $e) {
            return redirect()->route('pengolahan-nilai-siswa', [
                'kategori' => $kategori
            ])->with('warning', $e->getMessage());
        }
        $nilaisiswa = NilaiSiswa::where([
            'id' => $id,
            'kategori' => $kategori
        ])->first();

        if (!$nilaisiswa) {
            return redirect()->route('pengolahan-nilai-siswa', [
                'kategori' => $kategori
            ])->with('warning', 'Nilai siswa tidak tersedia');
        }

        $rombel = Rombel::where('idkelas', $nilaisiswa->idkelas)->get();
        $detailnilaisiswa = DetailNilaiSiswa::where('idnilaisiswa', $id)->get();

        $nilai = [];
        foreach ($detailnilaisiswa as $key => $value) {
            $nilai[$value->nisn] = $value->nilai;
        }

        $data['nilai'] = $nilai;
        $data['nilaisiswa'] = $nilaisiswa;
        $data['rombel'] = $rombel;
        return view('pages.nilaisiswa.input', $data);
    }

    public function simpanNilai(Request $request, String $kategori, String $id)
    {
        //
        $request->validate([
            'nilai' => 'required|array',
            'nilai.*' => 'integer|min:0|max:100',
        ]);

        try {
            $id = Crypt::decrypt($id);
        } catch (DecryptException $e) {
            return redirect()->route('nilai-siswa')->with('warning', $e->getMessage());
        }

        foreach ($request->nilai as $key => $value) {
            # code...
            DetailNilaiSiswa::updateOrCreate([
                'nisn' => $key,
                'idnilaisiswa' => $id,
            ], [
                'nilai' => $value,
            ]);
        }

        return redirect()->back()->with('success', 'Nilai siswa berhasil di simpan');
    }

    public function rekapNilaiSiswa()
    {
        $tahunajaran = $this->tahunajaran;
        $rekap = NilaiSiswa::where([
            'semester' => $tahunajaran->semester,
            'idtahunajaran' => $tahunajaran->id
        ])->groupBy('kode_matpel')->groupBy('idkelas')->get();

        $data['rekap'] = $rekap;
        $data['semester'] = $tahunajaran->semester;
        $data['idtahunajaran'] = $tahunajaran->id;

        return view('pages.nilaisiswa.rekap', $data);
    }

    public function showRekapNilaiSiswa(String $id)
    {
        $title = 'Data Rekap Pengolahan Nilai!';
        $text = "Yakin ingin menghapus data ini?";
        confirmDelete($title, $text);

        try {
            $data['id'] = $id;
            $id = explode('*', Crypt::decrypt($id));
            $idtahunajaran = $id[0];
            $semester = $id[1];
            $idkelas = $id[2];
            $kode_matpel = $id[3];
        } catch (DecryptException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }

        $data['kelas'] = Kelas::find($idkelas);
        $data['matpel'] = Matpel::where('kode_matpel', $kode_matpel)->first();

        $data['nilaisiswa'] = DetailNilaiSiswa::selectRaw("
            siswas.nisn,
            siswas.nama,
            AVG(IF(nilai_siswas.kategori = 'tugas', detail_nilai_siswas.nilai, NULL)) as nilai_tugas,
            AVG(IF(nilai_siswas.kategori = 'sumatif', detail_nilai_siswas.nilai, NULL)) as nilai_sumatif,
            AVG(IF(nilai_siswas.kategori = 'uts', detail_nilai_siswas.nilai, NULL)) as nilai_uts,
            AVG(IF(nilai_siswas.kategori = 'uas', detail_nilai_siswas.nilai, NULL)) as nilai_uas
        ")
            ->join('siswas', 'siswas.nisn', '=', 'detail_nilai_siswas.nisn')
            ->join('nilai_siswas', 'nilai_siswas.id', '=', 'detail_nilai_siswas.idnilaisiswa')
            ->whereHas('nilaisiswa', function ($query) use ($idtahunajaran, $semester, $kode_matpel, $idkelas) {
                $query->where([
                    'idtahunajaran' => $idtahunajaran,
                    'semester' => $semester,
                    'kode_matpel' => $kode_matpel,
                    'idkelas' => $idkelas,
                    'nip' => Auth::user()->staf->nip
                ]);
            })->groupBy('detail_nilai_siswas.nisn')->orderBy('siswas.nama')->get();

        $persen = PersentaseNilaiSiswa::where([
            'idtahunajaran' => $idtahunajaran,
            'semester' => $semester,
            'kode_matpel' => $kode_matpel,
            'idkelas' => $idkelas,
            'nip' => Auth::user()->staf->nip
        ])->first();

        $data['persen_tugas'] = ($persen->tugas ?? 25) / 100;
        $data['persen_sumatif'] = ($persen->sumatif ?? 25) / 100;
        $data['persen_uts'] = ($persen->uts ?? 25) / 100;
        $data['persen_uas'] = ($persen->uas ?? 25) / 100;
        // dd($data['nilaisiswa']);
        return view('pages.nilaisiswa.rekap_show', $data);
    }

    public function storePersentaseNilai(Request $request, String $id)
    {
        try {
            $id = explode('*', Crypt::decrypt($id));
            $idtahunajaran = $id[0];
            $semester = $id[1];
            $idkelas = $id[2];
            $kode_matpel = $id[3];
        } catch (DecryptException $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }

        $request->validate([
            'tugas' => 'required|numeric|min:0|max:100',
            'sumatif' => 'required|numeric|min:0|max:100',
            'uts' => 'required|numeric|min:0|max:100',
            'uas' => 'required|numeric|min:0|max:100',
        ]);

        PersentaseNilaiSiswa::updateOrCreate([
            'nip' => Auth::user()->staf->nip,
            'kode_matpel' => $kode_matpel,
            'idkelas' => $idkelas,
            'semester' => $semester,
            'idtahunajaran' => $idtahunajaran
        ], [
            'tugas' => $request->tugas,
            'sumatif' => $request->sumatif,
            'uts' => $request->uts,
            'uas' => $request->uas,
        ]);

        return redirect()->back()->with('success', 'Persentase nilai sudah di proses');
    }
}
