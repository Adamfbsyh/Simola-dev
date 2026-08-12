<?php

namespace App\Support;

class SimolaHelpDefaults
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function articles(): array
    {
        return [
            [
                'title' => 'Dashboard Monitoring Utama',
                'module' => 'Dashboard',
                'keywords' => [
                    'dashboard',
                    'monitoring',
                    'grafik',
                    'pelanggaran',
                    'kendala',
                    'accident',
                    'errorlog',
                    'pengemudi',
                ],
                'content' => <<<'TEXT'
Dashboard Monitoring Utama menampilkan ringkasan Pelanggaran, Kendala, Accident, Pengemudi Dinilai, dan Errorlog.

Bagian Perbandingan Bulanan membandingkan periode aktif dengan periode sebelumnya. Grafik di bawahnya digunakan untuk melihat tren dan kategori yang paling sering muncul.

Gunakan Pengaturan Dashboard bila ingin mengubah data yang ditampilkan, rentang grafik, atau metode perbandingan.
TEXT,
                'sort_order' => 10,
            ],
            [
                'title' => 'Cara menggunakan Upload Terpadu',
                'module' => 'Upload Terpadu',
                'keywords' => [
                    'upload',
                    'upload terpadu',
                    'pdf',
                    'laporan',
                    'unggah',
                    'pelanggaran',
                    'kendala',
                    'accident',
                ],
                'content' => <<<'TEXT'
Buka menu Upload Terpadu, pilih jenis laporan yang sesuai, lalu unggah file laporan.

Sebelum mengunggah, pastikan jenis laporan dan file sudah sesuai. SIMOLA akan membaca data yang diperlukan dan menyimpannya ke monitoring.

Jika sebuah PDF tidak dapat dibaca, periksa kembali file sumber. PDF yang rusak atau hasil scan tertentu dapat tidak memiliki teks yang bisa diekstrak.
TEXT,
                'sort_order' => 20,
            ],
            [
                'title' => 'Riwayat Upload dan file laporan',
                'module' => 'Upload Terpadu',
                'keywords' => [
                    'riwayat upload',
                    'file upload',
                    'hapus upload',
                    'preview',
                    'download',
                    'viewer',
                ],
                'content' => <<<'TEXT'
Riwayat Upload digunakan untuk melihat file yang sudah pernah masuk ke SIMOLA.

Gunakan pencarian, jenis laporan, dan tanggal untuk mempersempit data. File dapat dibuka melalui viewer/preview atau diunduh jika pengguna mempunyai hak akses.

Hapus file hanya jika memang diperlukan karena penghapusan dapat memengaruhi data monitoring yang berkaitan dengan upload tersebut.
TEXT,
                'sort_order' => 30,
            ],
            [
                'title' => 'Crosscheck K3.2',
                'module' => 'Crosscheck K3.2',
                'keywords' => [
                    'crosscheck',
                    'k3.2',
                    'hanya di pdf',
                    'hanya di k3.2',
                    'sesuai',
                    'duplikat',
                    'perlu diperiksa',
                ],
                'content' => <<<'TEXT'
Crosscheck K3.2 membandingkan kejadian K3.2 dengan laporan PDF.

Status "Sesuai" berarti pasangan data K3.2 dan PDF ditemukan. "Hanya di PDF" berarti kejadian ada pada PDF tetapi tidak ditemukan pada data K3.2. "Hanya di K3.2" berarti kebalikannya.

Gunakan tombol Lihat untuk membuka rincian tanggal, NOPOL, TLPG, jenis kejadian, jumlah file, kemungkinan duplikat, dan detail jam.
TEXT,
                'sort_order' => 40,
            ],
            [
                'title' => 'Laporan K3.2',
                'module' => 'Crosscheck K3.2',
                'keywords' => [
                    'laporan k3.2',
                    'laporan k32',
                    'preview k3.2',
                    'pdf k3.2',
                    'cetak k3.2',
                ],
                'content' => <<<'TEXT'
Laporan K3.2 digunakan untuk melihat hasil monitoring K3.2 dalam format laporan.

Gunakan Preview untuk memeriksa isi sebelum ekspor. Gunakan ekspor PDF jika laporan sudah sesuai dengan periode dan filter yang dibutuhkan.
TEXT,
                'sort_order' => 50,
            ],
            [
                'title' => 'Sinkronisasi Errorlog Spreadsheet',
                'module' => 'Errorlog',
                'keywords' => [
                    'errorlog',
                    'sinkron errorlog',
                    'spreadsheet errorlog',
                    'oauth k3-02',
                    'token expired',
                    'token revoked',
                    'sinkron ulang',
                ],
                'content' => <<<'TEXT'
Sinkronisasi Errorlog membaca data Errorlog dari Google Spreadsheet dan memasukkannya ke monitoring SIMOLA.

Koneksi menggunakan OAuth K3-02. Jika muncul pesan token expired atau revoked, buka Master Fleet > Google Workspace, hubungkan ulang K3-02, lalu kembali ke Errorlog dan pilih Sinkron Ulang.

Untuk penggunaan jangka panjang, OAuth Google sebaiknya tidak dibiarkan pada status Testing karena token testing dapat memerlukan autentikasi ulang.
TEXT,
                'sort_order' => 60,
            ],
            [
                'title' => 'Master Fleet',
                'module' => 'Master Fleet',
                'keywords' => [
                    'master fleet',
                    'master kendaraan',
                    'terminal',
                    'tlpg',
                    'spbe',
                    'spbu',
                    'perusahaan',
                    'profil jarak',
                ],
                'content' => <<<'TEXT'
Master Fleet adalah pusat data armada dan referensi operasional.

Di dalamnya terdapat Master Terminal, Master Perusahaan, Master Kendaraan, PC Set Utama, Draft Grouping, Import Spreadsheet, Google Workspace, Compare Data, dan Riwayat Perubahan sesuai fitur yang tersedia pada akun.

Pastikan jenis armada aktif sudah benar sebelum mengubah Master Kendaraan atau melakukan grouping.
TEXT,
                'sort_order' => 70,
            ],
            [
                'title' => 'Perbedaan MT LPG dan MT PERTASHOP',
                'module' => 'Master Fleet',
                'keywords' => [
                    'mt lpg',
                    'mt pertashop',
                    'pertashop',
                    'lpg',
                    'spbe',
                    'spbu',
                    'terminal berbeda',
                    'jenis armada',
                ],
                'content' => <<<'TEXT'
MT LPG dan MT PERTASHOP dipisahkan sebagai jenis armada yang berbeda.

Data kendaraan dan hasil grouping diproses sesuai jenis armada aktif. Referensi terminal/perusahaan juga dapat berbeda: operasional LPG menggunakan referensi LPG/SPBE, sedangkan Pertashop menggunakan referensi yang sesuai dengan SPBU/Pertashop.

Sebelum import, edit kendaraan, atau Draft Grouping, periksa pilihan Jenis Armada Aktif.
TEXT,
                'sort_order' => 80,
            ],
            [
                'title' => 'Draft Grouping dan jumlah PC',
                'module' => 'Master Fleet',
                'keywords' => [
                    'draft grouping',
                    'grouping',
                    'jumlah pc',
                    'tambah pc',
                    'generate pc',
                    'pc final',
                    'publish',
                    'profil jarak',
                ],
                'content' => <<<'TEXT'
Draft Grouping digunakan untuk menyiapkan pembagian kendaraan ke PC Final sebelum dipublikasikan menjadi PC Set Utama.

Pilih jenis armada yang benar, tentukan jumlah PC yang diinginkan, simpan jumlah PC, hitung Profil Jarak bila diperlukan, lalu Generate PC Final. Setelah hasil diperiksa dan edit manual selesai, gunakan Konfirmasi dan Publish.

Perubahan jumlah PC harus diikuti generate ulang agar pembagian otomatis menyesuaikan jumlah PC terbaru.
TEXT,
                'sort_order' => 90,
            ],
            [
                'title' => 'Compare data baru dari pengawas',
                'module' => 'Master Fleet',
                'keywords' => [
                    'compare',
                    'perbandingan data',
                    'data pengawas',
                    'excel pengawas',
                    'compare excel',
                    'data baru',
                    'tanpa mengubah data',
                ],
                'content' => <<<'TEXT'
Fitur Compare digunakan untuk membandingkan file baru dari pengawas dengan Master Fleet yang sedang aktif tanpa langsung mengubah data asli.

Unggah file Excel yang diberikan pengawas, jalankan preview/perbandingan, lalu periksa data yang sama, berubah, baru, atau tidak ditemukan. Terapkan perubahan hanya setelah hasil perbandingan sudah diperiksa.

Dengan cara ini file pengawas dapat digunakan sebagai bahan verifikasi sebelum data produksi diubah.
TEXT,
                'sort_order' => 100,
            ],
            [
                'title' => 'Riwayat Perubahan Master Fleet',
                'module' => 'Master Fleet',
                'keywords' => [
                    'riwayat perubahan',
                    'audit',
                    'audit trail',
                    'perubahan satu bulan',
                    'siapa mengubah',
                    'export perubahan',
                ],
                'content' => <<<'TEXT'
Riwayat Perubahan Master Fleet digunakan untuk melihat perubahan yang dicatat setelah fitur audit dipasang.

Filter dapat dilakukan berdasarkan periode, jenis armada, modul, aksi, data, atau pengguna. Gunakan Detail untuk melihat snapshot perubahan jika tersedia dan Export Excel jika atasan membutuhkan laporan perubahan.

Riwayat sebelum fitur audit dipasang tidak direkonstruksi agar laporan tidak berisi asumsi.
TEXT,
                'sort_order' => 110,
            ],
            [
                'title' => 'Google Workspace K3-02',
                'module' => 'Master Fleet',
                'keywords' => [
                    'google workspace',
                    'k3-02',
                    'oauth',
                    'hubungkan google',
                    'disconnect',
                    'connect',
                    'token google',
                ],
                'content' => <<<'TEXT'
Google Workspace K3-02 adalah koneksi OAuth yang digunakan SIMOLA untuk fitur Google Drive/Sheets yang membutuhkan akun K3-02.

Jika status tidak terhubung atau token expired/revoked, lakukan Disconnect bila diperlukan kemudian Hubungkan K3-02 kembali. Setelah login Google selesai, pastikan status di SIMOLA menjadi Terhubung.

Jangan membagikan access token, refresh token, client secret, atau file kredensial kepada pengguna lain.
TEXT,
                'sort_order' => 120,
            ],
            [
                'title' => 'Dark Mode dan tampilan SIMOLA',
                'module' => 'Tampilan',
                'keywords' => [
                    'dark mode',
                    'light mode',
                    'tema',
                    'tampilan gelap',
                    'warna',
                    'corporate navy',
                ],
                'content' => <<<'TEXT'
Tombol Light/Dark pada navbar mengubah Appearance SIMOLA.

Dark Mode menggunakan surface navy gelap dengan teks terang agar data mudah dibaca. Corporate Navy pada Dashboard berfungsi sebagai gaya/aksen Dashboard dan tidak menggantikan fungsi Light/Dark.

Jika setelah update tampilan masih terlihat lama, lakukan hard refresh browser dengan Ctrl+F5.
TEXT,
                'sort_order' => 130,
            ],
        ];
    }
}
