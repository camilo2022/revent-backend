<?php

namespace App\Traits;

trait ApiMessage
{
	private $messages = [
		'Success' => 'Operación completada con éxito. ',
		'ModelNotFoundException' => 'No se encontraron resultados para la búsqueda. ',
		'QueryException' => 'Error en la base de datos, por favor, inténtelo de nuevo más tarde. ',
		'Exception' => 'Se ha producido un error inesperado, por favor, inténtelo de nuevo más tarde. ',
        'ValidationException' => 'Error de validación. ',
        'UnauthorizedException' => 'Contraseña inválida. Asegúrate de escribirla correctamente. ',
        'AuthenticationException' => 'No autenticado. '
	];

    private $codes = [
		'Success' => 200,
		'ModelNotFoundException' => 404,
		'QueryException' => 500,
		'Exception' => 500,
        'ValidationException' => 422,
        'UnauthorizedException' => 403,
        'AuthenticationException' => 401
	];

    public function getMessage($key) : string
	{
        return isset($this->messages[$key]) ? $this->messages[$key] : 'Mensaje no encontrado';
    }

    public function getCode($key) : string
	{
        return isset($this->codes[$key]) ? $this->codes[$key] : 500;
    }
}
