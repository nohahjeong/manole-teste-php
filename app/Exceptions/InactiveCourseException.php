<?php

namespace App\Exceptions;

use Exception;

class InactiveCourseException extends Exception
{
    protected $message = 'Este curso está inativo e não aceita novas matrículas.';
}
