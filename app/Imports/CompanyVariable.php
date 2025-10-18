<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CompanyVariable implements OnEachRow, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    private $resultados = [];

    private $processedRows = 0;

    private $maxRows = 32000; // Límite máximo de filas

    public function onRow(Row $row)
    {
        // Limitar memoria cada 100 filas
        if ($this->processedRows % 100 === 0) {
            gc_collect_cycles();
        }

        $this->processedRows++;

        // Limitar el número máximo de filas procesadas
        if ($this->processedRows > $this->maxRows) {
            return;
        }

        $rowArray = $row->toArray();

        $numeroPersonal = $rowArray['numero_de_personal'] ?? null;
        $concepto = $rowArray['texto_explcc_nomina'] ?? null;
        $importe = $rowArray['importe'] ?? 0;
        $cantidad = $rowArray['cantidad'] ?? 0;
        $fechaNomina = $rowArray['final_del_periodo_en_nomina'] ?? null;

        // Filtro más eficiente
        if (! $numeroPersonal || ! in_array($concepto, ['Parte variable acumulada', 'Días calendario'])) {
            return;
        }

        $mes = $this->parseFechaNomina($fechaNomina);

        if (! isset($this->resultados[$numeroPersonal])) {
            $this->resultados[$numeroPersonal] = [
                'suma_importe' => 0,
                'suma_cantidad' => 0,
                'fecha' => $mes,
            ];
        }
        $this->resultados[$numeroPersonal]['suma_importe'] += (float) $importe;
        $this->resultados[$numeroPersonal]['suma_cantidad'] += (float) $cantidad;

        // Solo actualizar fecha si no está establecida
        if (! $this->resultados[$numeroPersonal]['fecha'] && $mes) {
            $this->resultados[$numeroPersonal]['fecha'] = $mes;
        }

    }

    private function parseFechaNomina($fechaNomina): ?int
    {
        if (empty($fechaNomina)) {
            return null;
        }

        try {
            // Si ya es un DateTime, usarlo directamente
            if ($fechaNomina instanceof \DateTime) {
                return (int) $fechaNomina->format('m');
            }

            // Si es numérico (formato Excel)
            if (is_numeric($fechaNomina)) {
                $fecha = Date::excelToDateTimeObject($fechaNomina);

                return (int) $fecha->format('m');
            }

            // Si es string, intentar parsear
            if (is_string($fechaNomina)) {
                return (int) Carbon::parse($fechaNomina)->month;
            }
        } catch (\Exception $e) {
            // Log silencioso - no interrumpir el procesamiento
        }

        return null;
    }

    public function getResultados(): Collection
    {
        $result = collect($this->resultados)
            ->map(function ($valores, $clave) {
                return [
                    'numero_de_personal' => $clave,
                    'suma_importe' => round($valores['suma_importe'], 2),
                    'suma_cantidad' => round($valores['suma_cantidad'], 2),
                    'fecha' => $valores['fecha'],
                ];
            })
            ->sortByDesc('suma_importe')
            ->values();

        // Limpiar memoria
        $this->resultados = [];
        gc_collect_cycles();

        return $result;
    }

    public function chunkSize(): int
    {
        return 500; // Procesar en chunks de 500 filas
    }

    public function batchSize(): int
    {
        return 500; // Mismo tamaño para batch
    }

    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }
}
