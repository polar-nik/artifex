<?php namespace PolarNik\Artifex\Exceptions;

use Exception;

class UnsupportedFileFormatException extends Exception
{
    protected $message = 'Unsupported file format';
}