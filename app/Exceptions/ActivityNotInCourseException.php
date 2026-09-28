<?php

namespace App\Exceptions;

use Exception;

class ActivityNotInCourseException extends Exception
{
    protected $message = 'Esta atividade não pertence ao curso da matrícula.';
}
