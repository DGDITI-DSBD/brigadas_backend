<?php 

namespace App\traits;
trait auth_t
{


    public function typeResponse($fat, $children, $data){

         $response = [
            'login' => [
                'login_success'            => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'Credenciales correctas'],
                'login_fail'               => ['ok' => false, 'backlog' => 1, 'backdata' => null, 'message_description' => 'Credenciales incorrectas'],
                'login_fail_to_trycatch'   => ['ok' => false, 'backlog' => 5, 'backdata' => null, 'message_description' => 'Error en el proceso de primer login'],
                'login_first_time'         => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'Primer inicio de sesión'],
                'login_check_success'      => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'Token válido'],
                'login_check_fail'         => ['ok' => false, 'backlog' => 3, 'backdata' => null, 'message_description' => 'Token inválido'],
                'login_check_waiting'      => ['ok' => true, 'backlog' => 0, 'backdata' => $data, 'message_description' => 'En espera de token'],
                'login_check_user_fail'    => ['ok' => false, 'backlog' => 4, 'backdata' => null, 'message_description' => 'Usuario inhabilitado'],
            ]
        ];



        return $response[$fat][$children];

      
    }
}