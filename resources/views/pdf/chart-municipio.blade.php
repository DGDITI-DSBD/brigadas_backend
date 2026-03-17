<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Asesorías por Municipio - Alimentación para el Bienestar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #374151; line-height: 1.4; }
        .page { padding: 24px; }
        .header { border-bottom: 3px solid #D4C19C; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { color: #6F1111; font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .sub { color: #6b7280; font-size: 9px; text-transform: uppercase; margin-top: 4px; }
        .header .meta { color: #9ca3af; font-size: 8px; margin-top: 8px; }
        .section-title { font-size: 11px; font-weight: bold; color: #6F1111; text-transform: uppercase; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #D4C19C; }
        .chart-block table { width: 100%; }
        .chart-block td { padding: 6px 0; font-size: 9px; }
        .chart-block .label { width: 45%; color: #4b5563; }
        .chart-block .bar-cell { width: 40%; }
        .chart-block .value { width: 15%; text-align: right; font-weight: bold; color: #8B6914; }
        .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Asesorías por Municipio</h1>
            <p class="sub">Alimentación para el Bienestar · Reportes Avanzados</p>
            <p class="meta">Generado el {{ $generado }} · Período: {{ $dateRange }}</p>
        </div>
        <div class="section-title">Distribución geográfica</div>
        <div class="chart-block">
            <table>
                @foreach($byMun as $row)
                @php $pct = $total > 0 ? min(100, ($row['total'] / $total) * 100) : 0; @endphp
                <tr>
                    <td class="label">{{ \Illuminate\Support\Str::limit($row['municipio'], 35) }}</td>
                    <td class="bar-cell">
                        <table cellpadding="0" cellspacing="0" style="width: 100%; background: #f3f4f6; border-radius: 2px;">
                            <tr>
                                <td style="width: {{ $pct }}%; background: #D4C19C; height: 12px;">&nbsp;</td>
                                <td style="height: 12px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td class="value">{{ $row['total'] }}</td>
                </tr>
                @endforeach
            </table>
        </div>
        <div class="footer">Gobierno del Estado de México · Alimentación para el Bienestar</div>
    </div>
</body>
</html>
