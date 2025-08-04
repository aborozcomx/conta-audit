<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;

class VariableImport implements WithMultipleSheets, ShouldQueue, WithChunkReading
{
    /**
     * @param Collection $collection
     */
    public $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function sheets(): array
    {
        return [
            new JanuaryImport($this->year),
            new FebruaryImport($this->year),
            new MarchImport($this->year),
            new AprilImport($this->year),
            new MayImport($this->year),
            new JuneImport($this->year),
            new JulyImport($this->year),
            new AugustImport($this->year),
            new SeptemberImport($this->year),
            new OctoberImport($this->year),
            new NovemberImport($this->year),
            new DecemberImport($this->year),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
