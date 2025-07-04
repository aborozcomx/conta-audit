<?php

use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

if (!function_exists('getYears')) {
    function getYears()
    {
        $periods = new CarbonPeriod('2022-01-01', '1 year', '2030-01-01');
        $years = [];
        foreach ($periods as $period) {
            $years[] = [
                'slug' => $period->format('Y'),
                'name' => $period->format('Y'),
            ];
        }

        return collect($years);
    }
}

if (!function_exists('getMonths')) {
    function getMonths($period = 0)
    {
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        //if ($period > 0)
        {
            return $months[$period];
        }
        return $months;
    }
}

if (!function_exists('getYears')) {
    function getMonths($year = '')
    {
        $years = [
            '2023' => '2023',
            '2024' => '2024',
            '2025' => '2025',
            '2026' => '2026',
            '2027' => '2027',
        ];

        if (!empty($year)) {
            return $years[$year];
        }

        return $years;
    }
}

if (!function_exists('getDespensaVariable')) {
    function getDespensaVariable($employee, $periods, $uma, $days, $year)
    {
        $despensa = DB::table('employee_payroll_concepts')
            ->select(DB::raw('SUM(amount) as total'))
            ->where('employee_id', $employee)
            ->whereIn('period', $periods)
            ->where('concepto', 'like', '%despensa%')
            ->where('year', $year)
            ->where('is_variable', true)
            ->first();


        $topeDespensa = $uma * $days;
        $totalDespensa = $despensa->total <= $topeDespensa ? $despensa->total : 0;

        return $totalDespensa;
    }
}

if (!function_exists('getExtrasVariable')) {
    function getExtrasVariable($employee, $periods, $uma, $days, $year)
    {
        $extras = DB::table('employee_payroll_concepts')
            ->select(DB::raw('SUM(amount) as total'))
            ->where('employee_id', $employee)
            ->whereIn('period', $periods)
            ->where('concepto', 'like', '%horas%')
            ->where('year', $year)
            ->where('is_variable', true)
            ->first();

        $topeExtras = $uma * $days;
        $totalExtras = $extras->total <= $topeExtras ? $extras->total : 0;

        return $totalExtras;
    }
}

if (!function_exists('getAsistenciaVariable')) {
    function getAsistenciaVariable($employee, $periods, $uma, $days, $year)
    {
        $asistencias = DB::table('employee_payroll_concepts')
            ->select(DB::raw('SUM(amount) as total'))
            ->where('employee_id', $employee)
            ->whereIn('period', $periods)
            ->where('concepto', 'like', '%asistencia%')
            ->where('is_variable', true)
            ->where('year', $year)
            ->first();

        $topeAsistencias = $uma * $days;
        $totalAsistencias = $asistencias->total <= $topeAsistencias ? $asistencias->total : 0;

        return $totalAsistencias;
    }
}

if (!function_exists('getPuntualidadVariable')) {
    function getPuntualidadVariable($employee, $periods, $uma, $days, $year)
    {
        $puntualidad = DB::table('employee_payroll_concepts')
            ->select(DB::raw('SUM(amount) as total'))
            ->where('employee_id', $employee)
            ->whereIn('period', $periods)
            ->where('concepto', 'like', '%puntualidad%')
            ->where('is_variable', true)
            ->where('year', $year)
            ->first();

        $topePuntualidad = $uma * $days;
        $totalPuntualidad = $puntualidad->total <= $topePuntualidad ? $puntualidad->total : 0;

        return $totalPuntualidad;
    }
}

if (!function_exists('getVariables')) {
    function getVariables($employee, $periods, $year)
    {
        $variables = ['%festivo%','%ajuste%', '%incentivo%', '%ahorro%', '%gasto%', '%puntualidad%', '%prima%', '%incapacidad%', '%cuota%', '%beca%', '%extra%', '%antiguedad%','%antigüedad%', '%retiro%', '%indemnizacion%', '%indemnización%', '%funeral%', '%comision%', '%comisión%', '%vale%', '%despensa%', '%ayuda%', '%jubilacion%', '%jubilación%', '%pension%', '%pensión%', '%haber%', '%alimentacion%', '%alimentación%', '%habitacion%', '%habitación%', '%asistencia%', '%viatico%', '%viático%', '%separacion%', '%separación%', '%otro%', '%compensación%', '%compensacion%'];

        $results = DB::table('employee_payroll_concepts')
            ->select(DB::raw('SUM(amount) as total'))
            ->where('employee_id', $employee)
            ->where('year', $year)
            ->whereIn('period', $periods)
            ->where('is_variable', true)
            ->first();

        return $results;
    }
}
