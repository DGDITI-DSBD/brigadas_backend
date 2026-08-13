<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW reporte_asesorias_respuestas AS 

                select  e.id as id_tipo_asesoria, 
                        c.id as asesor_atendio_id,
                        NOW()::date as fecha_formato,
						b.fecha_asesoria,
                        e.descripcion as tipo_asesoria, 
						a.ct_municipio,
                        a.municipio as municipio, 
                        --json.asesoria_id as asesoria_id,
                        a.folio_relacionado as folio, 
                        a.nombres as nombre,
                        a.primer_ap as primer_apellido, 
                        a.segundo_ap as segundo_apellido,
                        (case when a.genero = 'M' then 'MUJER' else 'HOMBRE' end) as genero,
                        a.edad, 
                        a.telefono, 
                        f.grado_estudios as escolaridad,
                        g.estado_civil as estado_civil,
                        a.fecha_nacimiento as fecha_nacimiento,
                        json.respuestas_to_json as respuestas
                from (
                with respuestas as (
                SELECT 
                    a.id AS asesoria_id,
                    d.id as pregunta_id,
                    d.descripcion, 
                    d.tipo_respuesta,
                    
                    
                        jsonb_agg(
                            to_jsonb(COALESCE(e.texto_descripcion, c.valor_contexto, NULL))
                            order by d.id
                        ) as respuestas_json
                            
                FROM br_asesorias a
                JOIN br_pacientes b ON b.id = a.paciente_id

                LEFT JOIN br_asesorias_preguntas_respuestas c 
                    ON c.asesoria_id = a.id 

                JOIN br_cat_preguntas d ON 
                d.id = c.pregunta_id

                LEFT JOIN br_cat_opciones e 
                    ON e.id = c.opcion_id 
                    AND e.pregunta_id = d.id

                GROUP BY 
                    a.id, d.id, d.descripcion, d.tipo_respuesta
                )

                select  r.asesoria_id,
                        jsonb_object_agg(
                            r.descripcion,
                            case when r.tipo_respuesta = 'opcion_multiple' then r.respuestas_json
                            else to_jsonb(r.respuestas_json->0)
                            end 
                            order by r.pregunta_id
                        ) as respuestas_to_json
                from respuestas r
                group by r.asesoria_id) json

                join br_asesorias b
                    on		b.id = json.asesoria_id
                join br_pacientes a 
                    on   a.id = b.paciente_id
                join br_directorio c 
                    on c.id = b.asesor_atendio_id
                join br_cat_tipo_asesoria e
                    on e.id = c.tipo_asesoria
                join br_cat_grado_estudios f
                    on f.ct_gdo_estudios = a.ct_gdo_estudios
                join br_cat_estado_civil g
                    on g.ct_edo_civil = a.ct_edo_civil
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS reporte_asesorias_respuestas');
    }
};
