<?php 

namespace App\traits;
trait carpeta_respuestas_t
{


    public function typeResponse($fat, $children, $data){

         $response = [
            'login' => [
                'login_success'            => ['ok' => true,     'backlog' => 0, 'backdata' => $data,   'message_description' => 'Credenciales correctas'              ],
                'login_fail'               => ['ok' => false,    'backlog' => 1, 'backdata' => null,    'message_description' => 'Credenciales incorrectas'            ],
                'login_fail_to_trycatch'   => ['ok' => false,    'backlog' => 5, 'backdata' => null,    'message_description' => 'Error en el proceso de primer login' ],
                'login_first_time'         => ['ok' => true,     'backlog' => 0, 'backdata' => $data,   'message_description' => 'Primer inicio de sesión'             ],
                'login_check_success'      => ['ok' => true,     'backlog' => 0, 'backdata' => $data,   'message_description' => 'Token válido'                        ],
                'login_check_fail'         => ['ok' => false,    'backlog' => 3, 'backdata' => null,    'message_description' => 'Token inválido'                      ],
                'login_check_waiting'      => ['ok' => true,     'backlog' => 0, 'backdata' => $data,   'message_description' => 'En espera de token'                  ],
                'login_check_user_fail'    => ['ok' => false,    'backlog' => 4, 'backdata' => null,    'message_description' => 'Usuario inhabilitado'                ],
            ],
            'directorios' => [
                'consulta_success'              => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta realizada con éxito'], 
                'consulta_sin_datos'            => ['ok' => true, 'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta no devolvió ningún dato'],
                'consulta_fail'                 => ['ok' => false,'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta del dato'],
                'usuario_not_directorio'        => ['ok' => true, 'backlog' => 3, 'backdata' => $data, 'message_description' => 'El usuario no tiene un directorio asignado'],
            ],
            'asesorias' => [
                    'consulta_success'                                  => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta realizada con éxito'], 
                    'consulta_exitosa'                                  => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta exitosa'], 
                    'consulta_sin_datos'                                => ['ok' => true,  'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta no devolvió ningún dato'],
                    'consulta_fail'                                     => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta del dato'],
                    'busqueda_fail'                                     => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la búsqueda'],
                    'consulta_asesoria_success'                         => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta de asesoría realizada con éxito'],
                    'consulta_asesoria_sin_datos'                       => ['ok' => true,  'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta de asesoría no devolvió ningún dato'],
                    'consulta_asesoria_fail'                            => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta de asesoría'],
                    'validacion_asesoria_fail'                          => ['ok' => false, 'backlog' => 3, 'backdata' => $data, 'message_description' => 'Falló en la validación de asesoría'],
                    'creacion_asesoria_success'                         => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Asesoría creada con éxito'],
                    'creacion_asesoria_fail'                            => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la creación de asesoría'],
                    'actualizacion_asesoria_success'                    => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Asesoría actualizada con éxito'],
                    'actualizacion_asesoria_fail'                       => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la actualización de asesoría'],
                    'consulta_preguntas_respuestas_success'             => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta de preguntas y respuestas realizada con éxito'],
                    'consulta_preguntas_respuestas_sin_datos'           => ['ok' => true,  'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta de preguntas y respuestas no devolvió ningún dato'],
                    'consulta_preguntas_respuestas_fail'                => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta de preguntas y respuestas'],
                    'consulta_pregunta_respuesta_sin_datos'             => ['ok' => true,  'backlog' => 2, 'backdata' => $data, 'message_description' => 'La pregunta y respuesta no fue encontrada'],
                    'excepcion_validar_preguntas_respuestas'            => ['ok' => false, 'backlog' => 5, 'backdata' => $data, 'message_description' => 'Error en la validación de preguntas y respuestas'],
                    'excepcion_transaccion_preguntas_respuestas'        => ['ok' => false, 'backlog' => 6, 'backdata' => $data, 'message_description' => 'Error en la transacción de preguntas y respuestas'],
                    'insercion_preguntas_respuestas_success'            => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Inserción de preguntas y respuestas realizada con éxito'],
                    'insercion_preguntas_respuestas_fail'               => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la inserción de preguntas y respuestas'],
                    'actualizacion_pregunta_respuesta_success'          => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Pregunta y respuesta actualizada con éxito'],
                    'actualizacion_pregunta_respuesta_fail'             => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la actualización de pregunta y respuesta'],
                    'eliminacion_pregunta_respuesta_success'            => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Pregunta y respuesta eliminada con éxito'],
                    'eliminacion_pregunta_respuesta_fail'               => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la eliminación de pregunta y respuesta'],
                    'canalizacion_asesoria_success'                     => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Asesoría canalizada con éxito'],
                    'canalizacion_asesoria_fail'                        => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la canalización de asesoría'],
                    'actualizacion_estado_asesoria_success'             => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Estado de asesoría actualizado con éxito'],
                    'actualizacion_estado_asesoria_fail'                => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la actualización del estado de asesoría'],
                    'creacion_asesoria_completa_success'                => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Asesoría completa creada con éxito (paciente, asesoría y respuestas)'],
                    'creacion_asesoria_completa_fail'                   => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la creación de la asesoría completa'],
            ],
            'pacientes' => [
                    'consulta_success'                                  => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta realizada con éxito'], 
                    'consulta_sin_datos'                                => ['ok' => true,  'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta no devolvió ningún dato'],
                    'consulta_fail'                                     => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta del dato'],
                    'paciente_existente'                                => ['ok' => true,  'backlog' => 3, 'backdata' => $data, 'message_description' => 'El paciente ya existe en el sistema'],
                    'paciente_creado_success'                           => ['ok' => true,  'backlog' => 0, 'backdata' => $data, 'message_description' => 'Paciente creado con éxito'],
                    'validacion_paciente_fail'                          => ['ok' => false, 'backlog' => 4, 'backdata' => $data, 'message_description' => 'Falló en la validación del paciente'],
                    'creacion_paciente_fail'                            => ['ok' => false, 'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la creación del paciente'],
            ],
            'catalogos' => [
                'consulta_success'              => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'Consulta realizada con éxito'], 
                'consulta_sin_datos'            => ['ok' => true, 'backlog' => 2, 'backdata' => $data, 'message_description' => 'La consulta no devolvió ningún dato'],
                'consulta_fail'                 => ['ok' => false,'backlog' => 1, 'backdata' => $data, 'message_description' => 'Falló en la consulta del dato'],
            ]
        ];

        return $response[$fat][$children];
    }
}