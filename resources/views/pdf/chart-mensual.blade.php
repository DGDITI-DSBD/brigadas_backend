<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Distribución Mensual por Especialidad - Alimentación para el Bienestar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #374151; line-height: 1.4; }
        .page { padding: 24px; }
        .header { border-bottom: 3px solid #6F1111; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { color: #6F1111; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .sub { color: #6b7280; font-size: 9px; text-transform: uppercase; margin-top: 4px; }
        .header .meta { color: #9ca3af; font-size: 8px; margin-top: 8px; }
        .section-title { font-size: 11px; font-weight: bold; color: #6F1111; text-transform: uppercase; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #6F1111; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        table.data th { background: rgba(111, 17, 17, 0.08); color: #6F1111; font-size: 8px; text-transform: uppercase; }
        table.data td { font-size: 9px; }
        table.data tbody tr:nth-child(even) { background: #f9fafb; }
        table.data tfoot td { background: rgba(111, 17, 17, 0.06); font-weight: bold; color: #6F1111; }
        table.data .num { text-align: center; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Distribución Mensual de Asesorías por Especialidad</h1>
            <p class="sub">Alimentación para el Bienestar · Reportes Avanzados</p>
            <p class="meta">Generado el {{ $generado }} · Período: {{ $dateRange }}</p>
        </div>
        <div class="section-title">Tabla de desglose mensual</div>
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
                @php $espColors = ['#6F1111', '#D4C19C', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899']; @endphp
                @foreach($especialidades as $ei => $esp)
                @php $rowTotal = 0; @endphp
                <tr>
                    <td>
                        <span style="display: inline-block; width: 8px; height: 8px; background: {{ $espColors[$ei % 7] }}; margin-right: 4px; vertical-align: middle;"></span>
                        {{ \Illuminate\Support\Str::limit($esp, 28) }}
                    </td>
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
        <div class="footer">Gobierno del Estado de México · Alimentación para el Bienestar</div>
    </div>
</body>
</html>
