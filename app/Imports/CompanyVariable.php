<?php

namespace App\Imports;

use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class CompanyVariable implements OnEachRow, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public $resultados = [];

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $numeroPersonal = $row['numero_de_personal'] ?? null;
        $concepto = $row['texto_explcc_nomina'] ?? null;
        $importe = $row['importe'] ?? 0;
        $cantidad = $row['cantidad'] ?? 0;
        $fechaNomina = $row['final_del_periodo_en_nomina'] ?? null;

        // Solo si aplica el filtro
        if (in_array($concepto, ['Parte variable acumulada', 'Días calendario']) && $numeroPersonal) {
            $mes = null;

            if (!empty($fechaNomina)) {
                try {
                    $fecha = Date::excelToDateTimeObject($fechaNomina);
                    $mes = Carbon::instance($fecha)->month;
                } catch (\Exception $e) {
                    $mes = null;
                }
            }

            if (!isset($this->resultados[$numeroPersonal])) {
                $this->resultados[$numeroPersonal] = [
                    'suma_importe' => 0,
                    'suma_cantidad' => 0,
                    'fecha' => $mes, // se puede sobreescribir, se asume 1 por empleado
                ];
            }

            $this->resultados[$numeroPersonal]['suma_importe'] += (float) $importe;
            $this->resultados[$numeroPersonal]['suma_cantidad'] += (float) $cantidad;
            $this->resultados[$numeroPersonal]['fecha'] = $mes; // puedes mantener el último valor
        }
    }

    public function getResultados()
    {
        return collect($this->resultados)
            ->map(function ($valores, $clave) {
                return [
                    'numero_de_personal' => $clave,
                    'suma_importe' => $valores['suma_importe'],
                    'suma_cantidad' => $valores['suma_cantidad'],
                    'fecha' => $valores['fecha'],
                ];
            })
            ->sortByDesc('suma_importe')
            ->values();
    }
}
