<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store_Usuario_Request extends FormRequest
{
    // Autorización para permitir que cualquier usuario pueda realizar esta solicitud
    public function authorize(): bool
    {
        return true;
    }

    // Reglas de validación para los campos del formulario de creación de usuario
    public function rules(): array
    {
        $tipo = $this->input('tipo_usuario');
        switch ($tipo) {
            case 'ADMINISTRADOR':
            case 'COORDINADOR':
                return [
                    'primer_ap_dir'     => 'required|string|max:255',
                    'segundo_ap_dir'    => 'required|string|max:255',
                    'nombres'           => 'required|string|max:255',
                    'nombre_usuario'    => 'required|string|max:255|unique:br_accesos,nombre_usuario',
                    'nombre_completo'   => 'nullable|string|max:255|unique:br_accesos,nombre_completo',
                    'password'          => [
                        'required',
                        'string',
                        'min:8',
                        'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'
                    ],
                    'estado'            => 'required',
                    'tipo_usuario'      => 'required|string|in:ADMINISTRADOR,COORDINADOR,ESPECIALISTA'
                ];
            case 'ESPECIALISTA':
                return [
                    'primer_ap_dir'     => 'required|string|max:255',
                    'segundo_ap_dir'    => 'required|string|max:255',
                    'nombres'           => 'required|string|max:255',
                    'telefono'          => 'required|string|max:20|unique:br_directorio,telefono',
                    'email'             => 'required|email|unique:br_directorio,email',
                    'nombre_usuario'    => 'required|string|max:255|unique:br_accesos,nombre_usuario',
                    'nombre_completo'   => 'nullable|string|max:255|unique:br_accesos,nombre_completo',
                    'password'          => [
                        'required',
                        'string',
                        'min:8',
                        'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'
                    ],
                    'estado'            => 'required',
                    'tipo_usuario'      => 'required|string|in:ADMINISTRADOR,COORDINADOR,ESPECIALISTA'
                ];
            // default:
            //     return [
            //         'tipo_usuario' => 'required|string|in:ADMINISTRADOR,COORDINADOR,ESPECIALISTA'
            //     ];
        }
    }

    // Mensajes personalizados para cada validación
    public function messages(): array
    {
        return [
            'primer_ap_dir.required'      => 'El apellido paterno es obligatorio.',
            'primer_ap_dir.max'           => 'El apellido paterno no puede exceder 255 caracteres.',
            'segundo_ap_dir.max'          => 'El apellido materno no puede exceder 255 caracteres.',
            'nombres.required'            => 'El nombre es obligatorio.',
            'nombres.max'                 => 'El nombre no puede exceder 255 caracteres.',
            'telefono.required'           => 'El teléfono es obligatorio.',
            'telefono.unique'             => 'El número de teléfono ya está registrado.',
            'telefono.max'                => 'El teléfono no puede exceder 20 caracteres.',
            'email.required'              => 'El correo electrónico es obligatorio.',
            'email.email'                 => 'El correo electrónico debe ser válido.',
            'email.unique'                => 'El correo electrónico ya está registrado.',
            'nombre_usuario.required'     => 'El nombre de usuario es obligatorio.',
            'nombre_usuario.unique'       => 'El nombre de usuario ya existe.',
            'nombre_usuario.max'          => 'El nombre de usuario no puede exceder 255 caracteres.',
            'nombre_completo.required'    => 'El nombre completo es obligatorio.',
            'nombre_completo.unique'      => 'Ya existe un usuario con el mismo nombre y apellidos.',
            'nombre_completo.max'         => 'El nombre completo no puede exceder 255 caracteres.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.min'                => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex'              => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un símbolo.',
            'estado.required'             => 'El estado es obligatorio.',
            // 'estado.boolean'              => 'El estado debe ser verdadero o falso.',
            'tipo_usuario.required'       => 'El tipo de usuario es obligatorio.',
            'tipo_usuario.in'             => 'El tipo de usuario debe ser ADMINISTRADOR, COORDINADOR o ESPECIALISTA.'
        ];
    }

    public function prepareForValidation()
    {
        $nombres = $this->input('nombres');
        $primer_ap = $this->input('primer_ap_dir');
        $segundo_ap = $this->input('segundo_ap_dir');
        $nombre_completo = trim($nombres . ' ' . $primer_ap . ' ' . $segundo_ap);
        $this->merge([
            'nombre_completo' => $nombre_completo
        ]);
    }
}
