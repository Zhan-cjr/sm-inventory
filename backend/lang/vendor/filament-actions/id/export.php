<?php

return [
    'modal' => [
        'heading' => 'Pilih Kolom Export',
        'form' => [
            'columns' => [
                'label' => 'Kolom',
                'actions' => [
                    'select_all' => [
                        'label' => 'Pilih Semua',
                    ],
                    'deselect_all' => [
                        'label' => 'Batalkan Pilihan Semua',
                    ],
                ],
            ],
        ],
        'actions' => [
            'export' => [
                'label' => 'Proses Export',
            ],
        ],
    ],
    'notifications' => [
        'completed' => [
            'title' => 'Export Selesai',
            'body' => 'Proses export data telah selesai. :successful_rows baris berhasil diexport.',
        ],
        'failed' => [
            'title' => 'Export Gagal',
        ],
    ],
    'file_name' => 'export-:export_id-:model',
];
