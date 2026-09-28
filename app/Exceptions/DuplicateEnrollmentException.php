<?php

namespace App\Exceptions;

use Exception;

class DuplicateEnrollmentException extends Exception
{
    protected $message = 'Este usuário já está matriculado neste curso.';
}
