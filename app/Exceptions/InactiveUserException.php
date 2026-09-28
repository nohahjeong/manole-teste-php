<?php

namespace App\Exceptions;

use Exception;

class InactiveUserException extends Exception
{
    protected $message = 'Este usuário está inativo.';
}
