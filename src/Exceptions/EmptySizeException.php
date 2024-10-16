<?php namespace PolarNik\Artifex\Exceptions;

use Exception;

class EmptySizeException extends Exception
{
    protected $message = 'At least one size must be specified';
}