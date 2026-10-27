<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


// FUNGSI STATUS STOK
function statusStok(int $stok): string
{
    if ($stok <= 0) {
        return 'Habis';
    }

    if ($stok <= 5) {
        return 'Menipis';
    }

    return 'Tersedia';
}


// READ + PENCARIAN + FILTER
Route::get('/produk', function (Request $request) {

    $kataKunci = trim((string) $request->query('q', ''));
    $kategoriProdukId = $request->query(
        'kategori_produk_id',
        ''
    );

    try {

        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT pr.id,
                       pr.kode_produk,
                       pr.nama_produk,
                       pr.sku,
                       pr.stok,
                       pr.kategori_produk_id,
                       kp.nama_kategori
                FROM produk AS pr
                JOIN kategori_produk AS kp
                  ON kp.id = pr.kategori_produk_id
                WHERE 1 = 1';

        $parameter = [];

        // PENCARIAN
        if ($kataKunci !== '') {

            $sql .= ' AND (
                pr.kode_produk LIKE :kode_produk
                OR pr.nama_produk LIKE :nama_produk
                OR pr.sku LIKE :sku
            )';

            $nilaiPencarian = '%' . $kataKunci . '%';

            $parameter['kode_produk'] = $nilaiPencarian;
            $parameter['nama_produk'] = $nilaiPencarian;
            $parameter['sku'] = $nilaiPencarian;
        }


        // FILTER KATEGORI
        if ($kategoriProdukId !== '') {

            $sql .= ' AND pr.kategori_produk_id =
                      :kategori_produk_id';

            $parameter['kategori_produk_id'] =
                (int) $kategoriProdukId;
        }


        $sql .= ' ORDER BY pr.kode_produk';


        $ambilProduk = $pdo->prepare($sql);

        $ambilProduk->execute($parameter);

        $daftarProduk = $ambilProduk->fetchAll(
            \PDO::FETCH_ASSOC
        );


        // AMBIL KATEGORI
        $ambilKategori = $pdo->prepare(
            'SELECT id, nama_kategori
             FROM kategori_produk
             ORDER BY nama_kategori'
        );

        $ambilKategori->execute();

        $daftarKategori = $ambilKategori->fetchAll(
            \PDO::FETCH_ASSOC
        );


        return view('produk', compact(
            'daftarProduk',
            'daftarKategori',
            'kataKunci',
            'kategoriProdukId'
        ));


    } catch (\Throwable $error) {

        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }

})->name('produk.index');


// CREATE
Route::post('/produk', function (Request $request) {

    $data = $request->validate([

        'kode_produk' => [
            'required',
            'string',
            'size:10',
            'regex:/^[A-Za-z0-9]+$/'
        ],

        'nama_produk' => [
            'required',
            'string',
            'min:3',
            'max:100'
        ],

        'sku' => [
            'required',
            'string',
            'min:5',
            'max:30'
        ],

        'stok' => [
            'required',
            'integer',
            'min:0',
            'max:500'
        ],

        'kategori_produk_id' => [
            'required',
            'integer'
        ],

    ]);


    try {

        $pdo = DB::connection()->getPdo();


        // CEK DUPLIKAT
        $cek = $pdo->prepare(
            'SELECT COUNT(*)
             FROM produk
             WHERE kode_produk = :kode_produk
                OR sku = :sku'
        );


        $cek->execute([

            'kode_produk' =>
                $data['kode_produk'],

            'sku' =>
                $data['sku']

        ]);


        if ((int) $cek->fetchColumn() > 0) {

            return back()
                ->withInput()
                ->with(
                    'gagal',
                    'Kode produk atau SKU sudah digunakan.'
                );
        }


        // INSERT
        $simpan = $pdo->prepare(
            'INSERT INTO produk
             (
                kode_produk,
                nama_produk,
                sku,
                stok,
                kategori_produk_id
             )
             VALUES
             (
                :kode_produk,
                :nama_produk,
                :sku,
                :stok,
                :kategori_produk_id
             )'
        );


        $simpan->execute([

            'kode_produk' =>
                $data['kode_produk'],

            'nama_produk' =>
                $data['nama_produk'],

            'sku' =>
                $data['sku'],

            'stok' =>
                $data['stok'],

            'kategori_produk_id' =>
                $data['kategori_produk_id']

        ]);


        return redirect()
            ->route('produk.index')
            ->with(
                'berhasil',
                'Data produk berhasil ditambahkan.'
            );


    } catch (\Throwable $error) {

        report($error);

        return back()
            ->withInput()
            ->with(
                'gagal',
                'Data produk gagal disimpan. Periksa koneksi atau query.'
            );
    }

})->name('produk.store');


// EDIT
Route::get('/produk/{id}/edit', function (int $id) {

    try {

        $pdo = DB::connection()->getPdo();


        $ambilProduk = $pdo->prepare(
            'SELECT id,
                    kode_produk,
                    nama_produk,
                    sku,
                    stok,
                    kategori_produk_id
             FROM produk
             WHERE id = :id'
        );


        $ambilProduk->execute([
            'id' => $id
        ]);


        $produk = $ambilProduk->fetch(
            \PDO::FETCH_ASSOC
        );


        if (!$produk) {

            return redirect()
                ->route('produk.index')
                ->with(
                    'gagal',
                    'Data produk tidak ditemukan.'
                );
        }


        $ambilKategori = $pdo->prepare(
            'SELECT id, nama_kategori
             FROM kategori_produk
             ORDER BY nama_kategori'
        );


        $ambilKategori->execute();


        $daftarKategori =
            $ambilKategori->fetchAll(
                \PDO::FETCH_ASSOC
            );


        return view(
            'produk-edit',
            compact(
                'produk',
                'daftarKategori'
            )
        );


    } catch (\Throwable $error) {

        report($error);

        return redirect()
            ->route('produk.index')
            ->with(
                'gagal',
                'Data produk gagal dibuka untuk diubah.'
            );
    }

})->name('produk.edit');


// UPDATE
Route::put('/produk/{id}', function (
    Request $request,
    int $id
) {

    $data = $request->validate([

        'kode_produk' => [
            'required',
            'string',
            'size:10',
            'regex:/^[A-Za-z0-9]+$/'
        ],

        'nama_produk' => [
            'required',
            'string',
            'min:3',
            'max:100'
        ],

        'sku' => [
            'required',
            'string',
            'min:5',
            'max:30'
        ],

        'stok' => [
            'required',
            'integer',
            'min:0',
            'max:500'
        ],

        'kategori_produk_id' => [
            'required',
            'integer'
        ],

    ]);


    try {

        $pdo = DB::connection()->getPdo();


        // CEK DUPLIKAT KECUALI DATA SENDIRI
        $cek = $pdo->prepare(
            'SELECT COUNT(*)
             FROM produk
             WHERE
                (
                    kode_produk = :kode_produk
                    OR sku = :sku
                )
                AND id <> :id'
        );


        $cek->execute([

            'kode_produk' =>
                $data['kode_produk'],

            'sku' =>
                $data['sku'],

            'id' => $id

        ]);


        if ((int) $cek->fetchColumn() > 0) {

            return back()
                ->withInput()
                ->with(
                    'gagal',
                    'Kode produk atau SKU sudah digunakan produk lain.'
                );
        }


        // UPDATE
        $ubah = $pdo->prepare(
            'UPDATE produk
             SET
                kode_produk = :kode_produk,
                nama_produk = :nama_produk,
                sku = :sku,
                stok = :stok,
                kategori_produk_id = :kategori_produk_id
             WHERE id = :id'
        );


        $ubah->execute([

            'kode_produk' =>
                $data['kode_produk'],

            'nama_produk' =>
                $data['nama_produk'],

            'sku' =>
                $data['sku'],

            'stok' =>
                $data['stok'],

            'kategori_produk_id' =>
                $data['kategori_produk_id'],

            'id' => $id

        ]);


        return redirect()
            ->route('produk.index')
            ->with(
                'berhasil',
                'Data produk berhasil diperbarui.'
            );


    } catch (\Throwable $error) {

        report($error);

        return back()
            ->withInput()
            ->with(
                'gagal',
                'Data produk gagal diperbarui. Periksa koneksi atau query.'
            );
    }

})->name('produk.update');


// DELETE
Route::delete('/produk/{id}', function (int $id) {

    try {

        $pdo = DB::connection()->getPdo();


        $hapus = $pdo->prepare(
            'DELETE FROM produk
             WHERE id = :id'
        );


        $hapus->execute([
            'id' => $id
        ]);


        if ($hapus->rowCount() === 0) {

            return redirect()
                ->route('produk.index')
                ->with(
                    'gagal',
                    'Data produk tidak ditemukan.'
                );
        }


        return redirect()
            ->route('produk.index')
            ->with(
                'berhasil',
                'Data produk berhasil dihapus.'
            );


    } catch (\Throwable $error) {

        report($error);

        return redirect()
            ->route('produk.index')
            ->with(
                'gagal',
                'Data gagal dihapus. Periksa koneksi atau relasi database.'
            );
    }

})->name('produk.destroy');