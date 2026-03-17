<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tablero General - Alimentación para el Bienestar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #374151; line-height: 1.4; }
        .page { padding: 24px; }

        .header { border-bottom: 3px solid #6F1111; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { color: #6F1111; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .sub { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
        .header .meta { color: #9ca3af; font-size: 8px; margin-top: 8px; }

        .stats { margin-bottom: 24px; }
        .stats table { width: 100%; border-collapse: collapse; }
        .stats td { padding: 12px 16px; border: 1px solid #e5e7eb; background: #f9fafb; vertical-align: top; width: 25%; }
        .stats td .label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .stats td .value { font-size: 22px; font-weight: bold; color: #6F1111; }
        .stats td:nth-child(1) .value { color: #6F1111; }
        .stats td:nth-child(2) .value { color: #3B82F6; }
        .stats td:nth-child(3) .value { color: #10B981; }
        .stats td:nth-child(4) .value { color: #D4C19C; }

        .section { margin-bottom: 24px; }
        .section-title { font-size: 11px; font-weight: bold; color: #6F1111; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #6F1111; }

        .chart-block { margin-bottom: 20px; }
        .chart-block table { width: 100%; }
        .chart-block td { padding: 4px 0; font-size: 9px; }
        .chart-block .label { width: 40%; color: #4b5563; }
        .chart-block .bar-cell { width: 45%; }
        .chart-block .bar-bg { background: #f3f4f6; height: 12px; border-radius: 2px; }
        .chart-block .bar-fill { background: #6F1111; height: 12px; border-radius: 2px; }
        .chart-block .value { width: 15%; text-align: right; font-weight: bold; color: #6F1111; }
        .chart-block.alt .bar-fill { background: #D4C19C; }
        .chart-block.alt .value { color: #8B6914; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { padding: 8px 10px; border: 1px solid #e5e7eb; }
        table.data th { background: rgba(111, 17, 17, 0.08); color: #6F1111; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.data td { font-size: 9px; }
        table.data tbody tr:nth-child(even) { background: #f9fafb; }
        table.data tfoot td { background: rgba(111, 17, 17, 0.06); font-weight: bold; color: #6F1111; }
        table.data .num { text-align: center; }

        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; text-align: center; }

        .two-col { width: 100%; }
        .two-col td { width: 50%; vertical-align: top; padding-right: 16px; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Tablero General - Reportes Avanzados</h1>
            <p class="sub">Alimentación para el Bienestar · Sistema Integral</p>
            <p class="meta">Generado el {{ $generado }} · Período: {{ $dateRange }}</p>
        </div>

        <div class="stats">
            <table>
                <tr>
                    <td><div class="label">Total Asesorías</div><div class="value">{{ number_format($total) }}</div></td>
                    <td><div class="label">Municipios Atendidos</div><div class="value">{{ count($municipios) }}</div></td>
                    <td><div class="label">Especialistas Activos</div><div class="value">{{ count($especialistas) }}</div></td>
                    <td><div class="label">Brigadistas</div><div class="value">{{ count($brigadistas) ?: '—' }}</div></td>
                </tr>
            </table>
        </div>

        <table class="two-col">
            <tr>
                <td>
                    <div class="section">
                        <div class="section-title">Asesorías por Especialidad</div>
                        <div class="chart-block">
                            <table>
                                @foreach(array_slice($byEsp, 0, 10) as $row)
                                @php $pct = $total > 0 ? min(100, ($row['total'] / $total) * 100) : 0; @endphp
                                <tr>
                                    <td class="label" style="width: 38%;">{{ \Illuminate\Support\Str::limit($row['especialidad'], 30) }}</td>
                                    <td class="bar-cell" style="width: 42%;">
                                        <table cellpadding="0" cellspacing="0" style="width: 100%; background: #f3f4f6; border-radius: 2px;">
                                            <tr>
                                                <td style="width: {{ $pct }}%; background: #6F1111; height: 10px;">&nbsp;</td>
                                                <td style="height: 10px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="value" style="width: 20%;">{{ $row['total'] }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="section">
                        <div class="section-title">Asesorías por Municipio</div>
                        <div class="chart-block alt">
                            <table>
                                @foreach(array_slice($byMun, 0, 10) as $row)
                                @php $pct = $total > 0 ? min(100, ($row['total'] / $total) * 100) : 0; @endphp
                                <tr>
                                    <td class="label" style="width: 38%;">{{ \Illuminate\Support\Str::limit($row['municipio'], 30) }}</td>
                                    <td class="bar-cell" style="width: 42%;">
                                        <table cellpadding="0" cellspacing="0" style="width: 100%; background: #f3f4f6; border-radius: 2px;">
                                            <tr>
                                                <td style="width: {{ $pct }}%; background: #D4C19C; height: 10px;">&nbsp;</td>
                                                <td style="height: 10px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="value" style="width: 20%;">{{ $row['total'] }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        @if(count($monthData) > 0)
        <div class="section" style="margin-top: 24px;">
            <div class="section-title">Distribución Mensual por Especialidad</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Especialidad</th>
                        @foreach($monthData as $m)
                        <th class="num">{{ $m['mes'] }}</th>
                        @endforeach
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($especialidades as $esp)
                    @php $rowTotal = 0; @endphp
                    <tr>
                        <td>{{ $esp }}</td>
                        @foreach($monthData as $m)
                        @php $val = $m['byEsp'][$esp] ?? 0; $rowTotal += $val; @endphp
                        <td class="num">{{ $val ?: '—' }}</td>
                        @endforeach
                        <td class="num">{{ $rowTotal }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Total del Mes</strong></td>
                        @foreach($monthData as $m)
                        <td class="num">{{ $m['total'] }}</td>
                        @endforeach
                        <td class="num">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <div class="footer">
            Gobierno del Estado de México · Alimentación para el Bienestar · Documento generado automáticamente
        </div>
    </div>
</body>
</html>
