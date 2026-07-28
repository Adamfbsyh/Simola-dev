<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role akun
    |--------------------------------------------------------------------------
    |
    | Role berfungsi sebagai kategori akun.
    | Hak akses sebenarnya akan dapat diatur per akun.
    |
    */

    'roles' => [
        'developer' => 'Developer',
        'spv' => 'SPV',
        'lead' => 'Lead',
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar modul dan permission
    |--------------------------------------------------------------------------
    */

    'modules' => [

        'dashboard' => [
            'label' => 'Dashboard',

            'permissions' => [
                'dashboard.view' =>
                    'Melihat Dashboard',

                'dashboard.settings' =>
                    'Mengubah Pengaturan Dashboard',
            ],
        ],

        'upload' => [
            'label' => 'Upload Terpadu',

            'permissions' => [
                'upload.view' =>
                    'Melihat Halaman Upload',

                'upload.create' =>
                    'Melakukan Upload',

                'upload.history' =>
                    'Melihat Riwayat Upload',

                'upload.delete' =>
                    'Menghapus Riwayat Upload',
            ],
        ],

        'pelanggaran' => [
            'label' => 'Pelanggaran',

            'permissions' => [
                'pelanggaran.view' =>
                    'Melihat Pelanggaran',

                'pelanggaran.create' =>
                    'Menambah Pelanggaran',

                'pelanggaran.update' =>
                    'Mengubah Pelanggaran',

                'pelanggaran.delete' =>
                    'Menghapus Pelanggaran',

                'pelanggaran.export' =>
                    'Mengunduh atau Export Pelanggaran',
            ],
        ],

        'kendala' => [
            'label' => 'Kendala',

            'permissions' => [
                'kendala.view' =>
                    'Melihat Kendala',

                'kendala.create' =>
                    'Menambah Kendala',

                'kendala.update' =>
                    'Mengubah Kendala',

                'kendala.delete' =>
                    'Menghapus Kendala',

                'kendala.export' =>
                    'Mengunduh atau Export Kendala',
            ],
        ],

        'accident' => [
            'label' => 'Accident',

            'permissions' => [
                'accident.view' =>
                    'Melihat Accident',

                'accident.create' =>
                    'Menambah Accident',

                'accident.update' =>
                    'Mengubah Accident',

                'accident.delete' =>
                    'Menghapus Accident',

                'accident.export' =>
                    'Mengunduh atau Export Accident',
            ],
        ],

        'driver_score' => [
            'label' => 'Skor Pengemudi',

            'permissions' => [
                'driver-score.view' =>
                    'Melihat Skor Pengemudi',

                'driver-score.export' =>
                    'Mengunduh Skor Pengemudi',
            ],
        ],

        'errorlog' => [
            'label' => 'Errorlog',

            'permissions' => [
                'errorlog.view' =>
                    'Melihat Errorlog',

                'errorlog.update' =>
                    'Mengubah Errorlog',

                'errorlog.close' =>
                    'Menutup Errorlog',

                'errorlog.delete' =>
                    'Menghapus Errorlog',

                'errorlog.export' =>
                    'Mengunduh atau Export Errorlog',
            ],
        ],

        'crosscheck_k32' => [
            'label' => 'Crosscheck K3.2',

            'permissions' => [
                'crosscheck.view' =>
                    'Melihat Crosscheck K3.2',

                'crosscheck.run' =>
                    'Menjalankan Crosscheck K3.2',

                'crosscheck.export' =>
                    'Mengunduh Hasil Crosscheck K3.2',
            ],
        ],

        'laporan_k32' => [
            'label' => 'Laporan K3.2',

            'permissions' => [
                'laporan-k32.view' =>
                    'Melihat Laporan K3.2',

                'laporan-k32.export' =>
                    'Mencetak atau Mengunduh Laporan K3.2',
            ],
        ],

                'master_fleet' => [
            'label' => 'Master Fleet',

            'permissions' => [
                'master-fleet.view' =>
                    'Melihat Modul Master Fleet',

                'fleet-terminal.create' =>
                    'Menambah TLPG atau Terminal',

                'fleet-terminal.update' =>
                    'Mengubah TLPG atau Terminal',

                'fleet-terminal.disable' =>
                    'Mengaktifkan atau Menonaktifkan TLPG',

                'fleet-company.create' =>
                    'Menambah SPBE atau Perusahaan',

                'fleet-company.update' =>
                    'Mengubah SPBE atau Perusahaan',

                'fleet-company.disable' =>
                    'Mengaktifkan atau Menonaktifkan SPBE',

                'fleet-distance.update' =>
                    'Mengubah Jarak SPBE ke TLPG',
            ],
        ],

        'synchronization' => [
            'label' => 'Sinkronisasi Data',

            'permissions' => [
                'sync.k32' =>
                    'Menjalankan Sinkronisasi K3.2',

                'sync.k3061' =>
                    'Menjalankan Sinkronisasi K3-06.1',
            ],
        ],

        'users' => [
            'label' => 'Manajemen Pengguna',

            'permissions' => [
                'users.view' =>
                    'Melihat Daftar Pengguna',

                'users.create' =>
                    'Membuat Akun Pengguna',

                'users.update' =>
                    'Mengubah Akun Pengguna',

                'users.disable' =>
                    'Mengaktifkan atau Menonaktifkan Akun',

                'users.reset-password' =>
                    'Mengatur Ulang Password Pengguna',

                'users.access' =>
                    'Mengatur Hak Akses Pengguna',
            ],
        ],

        'audit' => [
            'label' => 'Riwayat Aktivitas',

            'permissions' => [
                'audit.view' =>
                    'Melihat Riwayat Perubahan Akses',
            ],
        ],
    ],
];