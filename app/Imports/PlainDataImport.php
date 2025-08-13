<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeePayroll;
use App\Models\EmployeePayrollConcept;
use App\Models\Vacation;
use App\Models\Uma;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Illuminate\Contracts\Queue\ShouldQueue;

class PlainDataImport implements OnEachRow, WithHeadingRow, WithChunkReading, ShouldQueue
{
    protected $year;
    protected $company;
    protected $vacationCache = [];

    public function __construct($year, $company)
    {
        $this->year = $year;
        $this->company = $company;
    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $employee = Employee::firstOrCreate(
            ['social_number' => $row['numseguridadsocial']],
            [
                'name' => $row['nombrereceptor'],
                'clave' => $row['numempleado'],
                'puesto' => $row['pueston'] ?? 'N/A',
                'depto' => $row['departamento'] ?? 'N/A',
                'curp' => '',
                'rfc' => '--',
                'age' => '',
                'start_date' => $row['fechainiciorellaboral'],
                'number' => $row['numempleado'],
                'social_number' => $row['numseguridadsocial'],
                'base_salary' => $row['salariobasecotapor'],
                'daily_salary' => $row['salariobasecotapor'],
                'company_id' => $this->company->id,
            ]
        );

        if ($row['tiponomina'] == 'O - Ordinaria' || $row['tiponomina'] == 'O') {
            $year = $this->year;
            $month = $this->getMonthFromPeriod($row['periodo']);

            $carbon = new Carbon("last day of $month $year");
            $start_date = new Carbon($employee->start_date);
            $age = $carbon->diff($start_date);

            $vacation = $this->getVacation($age->y);
            if (!$vacation) {
                // Maneja si no hay vacaciones definidas para ese año
                return;
            }

            $daily_bonus = round($row['salariobasecotapor'] * $this->company->vacation_days / 365, 2);
            $vacations_import = $row['salariobasecotapor'] * $vacation->days;
            $vacation_bonus = round($vacations_import * ($this->company->vacation_bonus / 100) / 365, 2);

            $yearUma = ((int) $row['periodo'] === 1) ? $year - 1 : $year;
            $sdi_tope = Uma::where('year', $yearUma)->first();
            $sdi_tope = $sdi_tope->balance * 25;

            $sdi = round($row['salariobasecotapor'] + $daily_bonus + $vacation_bonus, 2);

            $salary = EmployeeSalary::where('year', $year)
                ->where('period', $row['periodo'])
                ->where('employee_id', $employee->id)
                ->first();

            $salaryData = [
                'period' => $row['periodo'],
                'year' => $year,
                'employee_id' => $employee->id,
                'category_id' => 1,
                'daily_salary' => $row['salariobasecotapor'],
                'daily_bonus' => $daily_bonus,
                'vacations_days' => $vacation->days,
                'vacations_import' => $vacations_import,
                'vacation_bonus' => $vacation_bonus,
                'sdi' => $sdi,
                'total_sdi' => 0,
                'sdi_limit' => $sdi_tope,
                'company_id' => $this->company->id,
                'variables' => '{}',
            ];

            if (!$salary) {
                EmployeeSalary::create($salaryData);
            } else {
                $salary->update($salaryData);
            }
        }

    }

    protected function getVacation($years)
    {
        if (!isset($this->vacationCache[$years])) {
            $this->vacationCache[$years] = Vacation::where('years', $years)->where('category_id', 1)->first();
        }
        return $this->vacationCache[$years];
    }

    protected function getMonthFromPeriod($period)
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        return $months[$period] ?? 'January';
    }

    public function chunkSize(): int
    {
        return 1500; // reduce si sigue consumiendo mucha memoria
    }
}


// namespace App\Imports;

// use App\Models\Company;
// use App\Models\Uma;
// use App\Models\Vacation;
// use Carbon\Carbon;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
// use Maatwebsite\Excel\Concerns\OnEachRow;
// use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
// use Maatwebsite\Excel\Concerns\SkipsOnError;
// use Maatwebsite\Excel\Concerns\SkipsOnFailure;
// use Maatwebsite\Excel\Concerns\WithChunkReading;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;
// use Maatwebsite\Excel\Row;
// use Maatwebsite\Excel\Validators\Failure;
// use Throwable;

// class PlainDataImport implements
//     ShouldQueue,
//     OnEachRow,
//     WithHeadingRow,
//     WithChunkReading,
//     WithValidation,
//     SkipsOnFailure,
//     SkipsOnError,
//     SkipsEmptyRows
// {
//     use \Maatwebsite\Excel\Concerns\SkipsFailures;
//     use \Maatwebsite\Excel\Concerns\SkipsErrors;

//     public function __construct(
//         protected int $year,
//         protected Company $company,
//         protected int $batchSize = 1000
//     ) {}

//     /** Buffers */
//     protected array $employeeRows   = [];   // employees (company_id, number) upsert
//     protected array $salaryRowsRaw  = [];   // salaries pendientes de resolver employee_id
//     protected array $employeeStartCache = []; // number => start_date (para evitar N+1)

//     /** ================= CORE ================= */

//     public function onRow(Row $row): void
//     {
//         $raw  = $row->toArray();

//         $norm = $this->normalizeRow($raw);
//         //dd($norm);

//         // Mínimos (validados por WithValidation)
//         $number = (int)$norm['number'];
//         $period = (int)$norm['period'];

//         // Campos base
//         $name     = (string)($norm['name'] ?? '');
//         $rfc      = (string)($norm['rfc'] ?? '');
//         $curp     = (string)($norm['curp'] ?? '');
//         $puesto   = (string)($norm['puesto'] ?? '');
//         $depto    = (string)($norm['depto'] ?? '');
//         $social   = $norm['social_number'] ?? null;
//         $base     = (float)($norm['base_salary'] ?? 0);   // salariobasecotapor
//         $daily    = (float)($norm['daily_salary'] ?? 0);  // SDI diario si viene
//         $tipoNom  = (string)($norm['tipo_nomina'] ?? ''); // ej: "O - Nómina ordinaria"
//         $tipoLtr  = strtoupper(Str::substr($tipoNom, 0, 1)); // "O" / "E" / etc.

//         // start_date: de la fila o de DB cacheada
//         $start = $norm['start_date'] ?? null;
//         if (!$start) {
//             $start = $this->getEmployeeStartDateFromDb($number);
//         }
//         $start = $start ? Carbon::parse($start)->toDateString() : null;

//         $now = now();

//         // === BUFFER EMPLOYEES (upsert por (company_id, number)) ===
//         $this->employeeRows[] = [
//             'company_id'    => (string)$this->company->id, // employees.company_id es varchar
//             'number'        => $number,
//             'name'          => $name,
//             'clave'        => $number,
//             'rfc'           => $rfc,
//             'curp'          => $curp,
//             'puesto'        => $puesto,
//             'depto'         => $depto,
//             'social_number' => $social,
//             'start_date'    => $start,
//             'base_salary'   => $base,
//             'daily_salary'  => $daily,
//             'created_at'    => $now,
//             'updated_at'    => $now,
//         ];

//         /** ---- Lógica especial para O - Ordinaria / O ---- */
//         $daily_bonus       = 0.0;
//         $vacations_days    = 0;
//         $vacations_import  = 0.0;
//         $vacation_bonus    = 0.0;
//         $sdi_limit         = 0.0;
//         $sdi_calc          = (float)($norm['sdi'] ?? 0); // fallback si no es O

//         if ($tipoNom === 'O - Ordinaria' || $tipoLtr === 'O') {
//             $year      = $this->year;
//             $monthName = $this->getMonthFromPeriod($period); // mapea periodo -> nombre de mes en inglés

//             // Edad laboral al último día de ese mes
//             $ageYears = 0;
//             if ($start) {
//                 $cutoff    = new Carbon("last day of $monthName $year");
//                 $startDate = new Carbon($start);
//                 $ageYears  = $cutoff->diff($startDate)->y;
//             }

//             // Vacaciones por antigüedad
//             $vacation = $this->getVacation($ageYears);
//             if ($vacation) {
//                 $vacations_days    = (int)$vacation->days;
//                 $daily_bonus       = round($base * $this->company->vacation_days / 365, 2);
//                 $vacations_import  = $base * $vacations_days;
//                 $vacation_bonus    = round($vacations_import * ($this->company->vacation_bonus / 100) / 365, 2);

//                 // UMA y tope 25 UMA
//                 $yearUma  = ($period === 1) ? $year - 1 : $year;
//                 $uma      = Uma::where('year', $yearUma)->first();
//                 $sdi_limit = $uma ? $uma->balance * 25 : 0;

//                 // SDI calculado (opcionalmente podrías topearlo)
//                 $sdi_calc  = round($base + $daily_bonus + $vacation_bonus, 2);
//                 // $sdi_calc = min($sdi_calc, $sdi_limit); // si decides aplicar tope
//             } else {
//                 // si no hay definición de vacaciones para esa antigüedad, guardamos sin extras
//                 $vacations_days = 0;
//             }
//         }

//         // === BUFFER SALARIES (resolvemos employee_id en flush) ===
//         $this->salaryRowsRaw[] = [
//             'company_id'      => (string)$this->company->id,
//             'number'          => $number,
//             'period'          => $period,
//             'year'            => (int)$this->year,
//             'daily_salary'    => $base,
//             'sdi'             => $sdi_calc,
//             'sdi_variable'    => (float)($norm['sdi_variable'] ?? 0),
//             'total_sdi'       => (float)($norm['total_sdi'] ?? 0),
//             'vacations_days'  => $vacations_days,
//             'vacations_import'=> $vacations_import,
//             'vacation_bonus'  => $vacation_bonus,
//             'sdi_limit'       => $sdi_limit,
//             'variables'       => $norm['variables'] ?? null, // si decides guardar JSON
//             'created_at'      => $now,
//             'updated_at'      => $now,
//             'daily_bonus'      => $daily_bonus,

//         ];

//         // Flush por lotes
//         if (count($this->employeeRows) >= $this->batchSize || count($this->salaryRowsRaw) >= $this->batchSize) {
//             $this->flush();
//         }
//     }

//     protected function flush(): void
//     {
//         DB::transaction(function () {
//             // 1) Upsert employees (clave: company_id, number)
//             if ($this->employeeRows) {
//                 DB::table('employees')->upsert(
//                     $this->employeeRows,
//                     ['rfc'],
//                     [
//                         'name','clave','rfc','curp','puesto','depto','social_number',
//                         'start_date','base_salary','daily_salary','updated_at'
//                     ]
//                 );
//             }

//             // 2) Resolver employee_id
//             if ($this->salaryRowsRaw) {
//                 $numbers = collect($this->salaryRowsRaw)->pluck('number')->unique()->values()->all();

//                 $idsByNumber = DB::table('employees')
//                     ->where('company_id', (string)$this->company->id)
//                     ->whereIn('number', $numbers)
//                     ->pluck('id', 'number'); // number => id

//                 // 3) Upsert employee_salaries (clave: company_id, employee_id, year, period)
//                 $salaryRows = [];
//                 foreach ($this->salaryRowsRaw as $r) {
//                     $empId = $idsByNumber[$r['number']] ?? null;
//                     if (!$empId) continue;

//                     $salaryRows[] = [
//                         'company_id'     => (int)$this->company->id, // employee_salaries.company_id es int
//                         'employee_id'    => (int)$empId,
//                         'year'           => (int)$r['year'],
//                         'period'         => (int)$r['period'],
//                         'daily_salary'   => (float)$r['daily_salary'],
//                         'sdi'            => (float)$r['sdi'],
//                         'sdi_variable'   => (float)$r['sdi_variable'],
//                         'total_sdi'      => (float)$r['total_sdi'],
//                         'vacations_days' => (int)$r['vacations_days'],
//                         'vacations_import'=> (float)$r['vacations_import'],
//                         'vacation_bonus' => (float)$r['vacation_bonus'],
//                         'daily_bonus' => (float)$r['daily_bonus'],
//                         'sdi_limit'      => (float)$r['sdi_limit'],
//                         'variables'      => $r['variables'],
//                         'created_at'     => $r['created_at'],
//                         'updated_at'     => $r['updated_at'],
//                         'category_id' => 1,
//                         'variables' => '{}',
//                     ];
//                 }

//                 if ($salaryRows) {
//                     DB::table('employee_salaries')->upsert(
//                         $salaryRows,
//                         ['company_id','employee_id','year','period'],
//                         [
//                             'daily_salary','sdi','sdi_variable','total_sdi',
//                             'vacations_days','vacations_import','vacation_bonus', 'daily_bonus',
//                             'sdi_limit','variables','updated_at', 'category_id', 'variables'
//                         ]
//                     );
//                 }
//             }

//             // 4) limpiar buffers
//             $this->employeeRows  = [];
//             $this->salaryRowsRaw = [];
//         });
//     }

//     public function __destruct()
//     {
//         if ($this->employeeRows || $this->salaryRowsRaw) {
//             $this->flush();
//         }
//     }

//     /** ================= VALIDATION ================= */

//     public function rules(): array
//     {
//         return [
//             'number'        => ['required','integer','min:1'],
//             'period'        => ['required','integer','min:1'],
//             'name'          => ['nullable','string','max:255'],
//             'rfc'           => ['nullable','string','max:255'],
//             'curp'          => ['nullable','string','max:255'],
//             'puesto'        => ['nullable','string','max:255'],
//             'depto'         => ['nullable','string','max:255'],
//             'social_number' => ['nullable','string','max:255'],
//             'start_date'    => ['nullable','date'],
//             'base_salary'   => ['nullable','numeric'],
//             'daily_salary'  => ['nullable','numeric'],
//             'sdi'           => ['nullable','numeric'],
//             'sdi_variable'  => ['nullable','numeric'],
//             'total_sdi'     => ['nullable','numeric'],
//             'tipo_nomina'   => ['nullable','string','max:255'],
//             'variables'     => ['nullable','string'],
//         ];
//     }

//     public function prepareForValidation($row, $index)
//     {
//         return $this->normalizeRow($row);
//     }

//     public function customValidationMessages()
//     {
//         return [
//             'number.required' => 'El "Número de empleado" es obligatorio.',
//             'period.required' => 'El "Periodo" es obligatorio.',
//         ];
//     }

//     public function customValidationAttributes()
//     {
//         return [
//             'number' => 'Número de empleado',
//             'period' => 'Periodo',
//         ];
//     }

//     /** SkipsOnFailure */
//     public function onFailure(Failure ...$failures)
//     {
//         foreach ($failures as $failure) {
//             logger()->warning('Import validation failure', [
//                 'row'       => $failure->row(),
//                 'attribute' => $failure->attribute(),
//                 'errors'    => $failure->errors(),
//                 'values'    => $failure->values(),
//             ]);
//         }
//     }

//     /** SkipsOnError */
//     public function onError(Throwable $e)
//     {
//         logger()->error('Import row error', ['message' => $e->getMessage()]);
//     }

//     public function chunkSize(): int
//     {
//         return 1000;
//     }

//     /** ================= HELPERS ================= */

//     /** Normaliza encabezados y valores del XLSX a claves limpias */
//     protected function normalizeRow(array $row): array
//     {
//         $cleanKey = function (string $k): string {
//             return Str::of($k)->trim()->lower()
//                 ->replace([' ', '-', '.', 'á','é','í','ó','ú','ñ'], ['_','_','', 'a','e','i','o','u','n'])
//                 ->__toString();
//         };

//         $src = [];
//         foreach ($row as $k => $v) {
//             $src[$cleanKey((string)$k)] = is_string($v) ? trim($v) : $v;
//         }



//         $map = fn(array $keys, $default = null) =>
//             collect($keys)->map(fn($k) => $src[$k] ?? null)->first(fn($v) => $v !== null) ?? $default;

//         // NSS suele venir como float; devolver string sin .0
//         $nss = $map(['numseguridadsocial','num_seguridad_social','social_number']);
//         if ($nss !== null) $nss = preg_replace('/\.0+$/', '', (string)$nss);

//         return [
//             // mínimos
//             'number'        => (int) ($map(['numempleado','numero_empleado','numeroempleado','number']) ?: 0),
//             'period'        => (int) ($map(['periodo','period']) ?: 0),

//             // opcionales
//             'name'          => $map(['nombrereceptor','nombre_receptor','name']),
//             'rfc'           => $map(['rfc_receptor','rfc']),
//             'curp'          => $map(['curp']),
//             'puesto'        => $map(['puesto']),
//             'depto'         => $map(['departamento','depto']),
//             'social_number' => $nss,
//             'start_date'    => $map(['fechainiciorellaboral','fecha_inicio_rel_laboral','start_date']),
//             'base_salary'   => $map(['salariobasecotapor','sbc','base_salary'], 0),
//             'daily_salary'  => $map(['salariodiariointegrado','sdi_diario','daily_salary'], 0),

//             // extra salarios
//             'sdi'           => $map(['sdi'], 0),
//             'sdi_variable'  => $map(['sdi_variable'], 0),
//             'total_sdi'     => $map(['total_sdi'], 0),
//             'tipo_nomina'   => $map(['tiponomina','tipo_nomina']),
//             'variables'     => $map(['variables']),
//         ];
//     }

//     /** Obtiene start_date de DB y lo cachea por número de empleado */
//     protected function getEmployeeStartDateFromDb(int $number): ?string
//     {
//         if (array_key_exists($number, $this->employeeStartCache)) {
//             return $this->employeeStartCache[$number];
//         }
//         $start = DB::table('employees')
//             ->where('company_id', (string)$this->company->id)
//             ->where('number', $number)
//             ->value('start_date');

//         return $this->employeeStartCache[$number] = $start ? (string)$start : null;
//     }

//     /** Mapea periodo (1..12/24) a nombre de mes en inglés para Carbon ("January"...) */
//     protected function getMonthFromPeriod(int $period): string
//     {
//         // Ajusta si tus periodos no son mensuales. Por defecto: 1..12 => Jan..Dec.
//         $m = max(1, min(12, $period));
//         return Carbon::create()->month($m)->format('F');
//     }

//     /** Busca la definición de vacaciones por años de antigüedad */
//     protected function getVacation(int $years): ?Vacation
//     {
//         // Ajusta según tu esquema (ej: tabla vacations con columna years y days)
//         return Vacation::where('years', '<=', $years)->orderByDesc('years')->first();
//     }
// }
