<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Traits\carpeta_respuestas_t;
use Tymon\JWTAuth\Facades\JWTAuth;
use Log;

class authController extends Controller
{

     use carpeta_respuestas_t;

     protected $tipo_response = 'login';
     protected $tipo_hijo     = null;
     protected $construirINF  = [];
     protected $cookie;

     protected function login(Request $request){

            /* 
                variables:
                obtieneCRD: obtiene credenciales - boolean
                validarTKN: validar token - boolean
                construirINF: construir información
            */

        Log::info("credenciales", [$request->all()]);
        $obtieneCRD = $request -> only('nombre_usuario', 'password');
        $token = JWTAuth::attempt($obtieneCRD);
        $this -> construirINF = [];
        $this -> construirINF = [ 'token'        => $token ? $token : '',
                                  'member_auth'  => $token ? JWTAuth::user() : null,
                                  'directorios'   => $token ? JWTAuth::user()->directorios()->get() : null,
                                  'permissions'   => $token ? JWTAuth::user()->usuarioPrivilegios()->get() : null
                                  ];

        $this -> tipo_hijo = null;

        if($token){
            Log::info('user', [JWTAuth::user()]);
            if(JWTAuth::user() -> estado != 1){
                    $this -> tipo_hijo = 'login_check_user_fail';
                    return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
            }
            $this -> cookie = cookie(
                'token',
                $token,
                60,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
            $this -> tipo_hijo = 'login_success'; return response()->json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF))
            ->withCookie($this->cookie);
        }else{
            $this -> tipo_hijo = 'login_fail'; return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
        }
    

    }

    protected function checkingUser(){
        
            
            $token          = JWTAuth::getToken();
            Log::info("TOKEN", [$token]);
            if($token){
            $assignmentUser = JWTAuth::toUser($token);
            Log::info("directorio", [$assignmentUser ? $assignmentUser->directorios()->get() : null]);
            $this -> construirINF = ['token'        => (string)$token,
                                     'member_check' => $assignmentUser ? JWTAuth::user() : null,
                                     'directorios' => $assignmentUser ? JWTAuth::user()->directorios()->get() : null,
                                     'permissions' => $assignmentUser ? JWTAuth::user()->usuarioPrivilegios()->get() : null,
                                     'status'       => $assignmentUser && JWTAuth::user() -> estado === 1 ? 'Authorization' : 'Not-Authorization'];
            
            if($assignmentUser){
                if(JWTAuth::user() -> estado != 1){
                    $this -> tipo_hijo = 'login_check_user_fail';
                    return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
                    
                }
                $this -> tipo_hijo = 'login_check_success';
                $this -> cookie = cookie(
                'token',
                $token,
                60,
                '/',
                null,
                true,
                true,
                false,
                'Strict'
            );
                return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF))
                ->withCookie($this -> cookie);
            }else{
                $this -> tipo_hijo = 'login_check_fail';
                return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
            }
            }else{
                Log::info("entre no auth", [$token]);
                $this -> construirINF = ['token'    => null,
                                     'member_check' => null,
                                     'status'       => 'Not-Authorization'];
                $this -> tipo_hijo = 'login_check_waiting';
                return response() -> json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
            }


    }

    public function updateFirstLogin(Request $request){

        try {

            $this -> tipo_hijo = 'login_first_time';

              $user = JWTAuth::user();
              $user -> first_login = 0;
              $user -> save();

            $this -> construirINF = [
                'member_check'  => $user,
                'directorios' => $user ? $user->directoriosActivos()->get() : null,
                'permissions' => $user ? $user->usuarioPrivilegios()->get() : null
            ];

             return response()->json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));

        } catch (\Exception $e) {
            $this -> tipo_hijo = 'login_fail_to_trycatch';
            $this -> construirINF = [
                'error_message' => $e -> getMessage(),
                'error_line'    => $e -> getLine()
            ];
            return response()->json($this -> typeResponse($this -> tipo_response, $this -> tipo_hijo, $this -> construirINF));
        }
      

        return response()->json($this->typeResponse($this -> tipo_response, 'login_first_time', [
            'token'        => $request -> token,
            'member_auth'  => $user,
            'directorios' => $user ? $user->directoriosActivos()->get() : null,
            'permissions' => $user ? $user->usuarioPrivilegios()->get() : null
        ]));
    }

 
}
