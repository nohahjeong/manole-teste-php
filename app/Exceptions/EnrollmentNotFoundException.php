<?php

namespace App\Exceptions;

use Exception;

class EnrollmentNotFoundException extends Exception
{
    protected $message = 'Este usuário não está matriculado neste curso.';
}
