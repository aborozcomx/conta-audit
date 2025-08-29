<table>
    <thead>
        <tr>
            <th >Año</th>
            <th class="w-[100px]">Periodo</th>
            <th class="w-[100px]">Empleado</th>
            <th class="w-[100px]">RFC</th>
            <th class="w-[100px]">NSS</th>
            <th class="w-[100px]">SDC</th>
            <th>Días</th>
            <th>Ausencias</th>
            <th>Incapacidad</th>
            <th>Días totales</th>
            <th>Diferencia días</th>
            <th>Cotización E y M</th>
            <th>Cotización RT y GPS</th>
            <th>Cotización I y V</th>
            <th>Cuota fija</th>
            <th>SDMG</th>
            <th>En dinero</th>
            <th>Invalidez y vida</th>
            <th>Pensionados</th>
            <th>Prima de riesgo</th>
            <th>Guarderías</th>
            <th>Total auditoría</th>
            <th>Total Compañía</th>
            <th>Dif. a pagar</th>
        </tr>
    </thead>
    <tbody>
    @foreach($quotas as $quota)
        <tr>
            <td>{{ $quota->year }}</td>
            <td>{{ $quota->period }}</td>
            <td>{{ $quota->name }}</td>
            <td>{{ $quota->rfc }}</td>
            <td>{{ $quota->social_number }}</td>
            <td>{{ $quota->base_salary }}</td>
            <td>{{ $quota->days }}</td>
            <td>{{ $quota->absence }}</td>
            <td>{{ $quota->incapacity }}</td>
            <td>{{ $quota->total_days }}</td>
            <td>{{ $quota->difference_days }}</td>
            <td>{{ $quota->base_price_em }}</td>
            <td>{{ $quota->base_price_rt }}</td>
            <td>{{ $quota->base_price_iv }}</td>
            <td>{{ $quota->fixed_price }}</td>
            <td>{{ $quota->sdmg }}</td>
            <td>{{ $quota->in_cash }}</td>
            <td>{{ $quota->disability_health }}</td>
            <td>{{ $quota->pensioners }}</td>
            <td>{{ $quota->risk_price }}</td>
            <td>{{ $quota->nurseries }}</td>
            <td>{{ $quota->total_audit }}</td>
            <td>{{ $quota->total_company }}</td>
            <td>{{ $quota->difference }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
