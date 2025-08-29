<table>
    <thead>
        <tr>
            <th class="w-[100px]">Año</th>
            <th class="w-[100px]">Periodo</th>
            <th class="w-[100px]">Empleado</th>
            <th class="w-[100px]">RFC</th>
            <th class="w-[100px]">NSS</th>
            <th class="w-[100px]">Fecha inicio</th>
            <th>Sueldo diario</th>
            <th>Aguinaldo diario</th>
            <th>Dias de Vacaciones</th>
            <th>Importe de Vacaciones</th>
            <th>Prima vacacional</th>
            <th>SDI fijo</th>
            <th>SDI variable</th>
            <th>SDI total</th>
            <th>Tope SDI</th>
            <th>SDI aud.</th>
            <th>IMSS</th>
            <th>Diferencia</th>
        </tr>
    </thead>
    <tbody>
    @foreach($salaries as $salary)
        <tr>
            <td>{{ $salary->year }}</td>
            <td>{{ $salary->period }}</td>
            <td>{{ $salary->name }}</td>
            <td>{{ $salary->rfc }}</td>
            <td>{{ $salary->social_number }}</td>
            <td>{{ $salary->start_date }}</td>
            <td>{{ $salary->daily_salary }}</td>
            <td>{{ $salary->daily_bonus }}</td>
            <td>{{ $salary->vacations_days }}</td>
            <td>{{ $salary->vacations_import }}</td>
            <td>{{ $salary->vacation_bonus }}</td>
            <td>{{ $salary->sdi }}</td>
            <td>{{ $salary->sdi_variable }}</td>
            <td>{{ $salary->total_sdi }}</td>
            <td>{{ $salary->sdi_limit }}</td>
            <td>{{ $salary->sdi_aud }}</td>
            <td>{{ $salary->sdi_quoted }}</td>
            <td>{{ $salary->difference }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
