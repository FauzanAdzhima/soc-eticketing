<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Label status tiket (nilai kanonikal backend / DB tetap bahasa Inggris).
    |--------------------------------------------------------------------------
    */
    'coordinator_badge_labels' => [
        'Closed' => 'Ditutup',
        'Validated' => 'Tervalidasi',
        'Reopened' => 'Dibuka Kembali',
        'Awaiting Verification' => 'Menunggu Verifikasi',
        'Open' => 'Dibuka',
        'On Progress' => 'Dalam Penanganan',
        'Report Rejected' => 'Laporan Ditolak',
    ],

    /*
    |--------------------------------------------------------------------------
    | Label sub-status alur penanganan (nilai DB = konstanta Ticket::*).
    |--------------------------------------------------------------------------
    */
    'sub_status_labels' => [
        'Triage' => 'Triase',
        'Analysis' => 'Analisis',
        'Response' => 'Penanganan',
        'Resolution' => 'Resolusi',
    ],

    /** Label kolom ringkas di header chat tiket */
    'chat_field_status' => 'Status',
    'chat_field_sub_status' => 'Sub-Status',
];
