<?php

// app/Imports/RawCfdiImport.php
namespace App\Imports;

use App\Models\CfdiImport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Illuminate\Contracts\Queue\ShouldQueue;

class RawCfdiImport implements OnEachRow, WithChunkReading, WithHeadingRow, ShouldQueue
{
    protected $year;
    protected $userId;
    protected $companyId;

    public function __construct(int $year, int $userId, int $companyId)
    {
        $this->year = $year;
        $this->userId = $userId;
        $this->companyId = $companyId;
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();

        if (!isset($data['uuid'])) return;

        CfdiImport::create([
            'data' => json_encode($data),
            'year' => $this->year,
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }
}

