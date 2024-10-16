<?php namespace PolarNik\Artifex\Exceptions;

use Exception;

class EmptyImagesizeDataException extends Exception
{
    protected $message = 'File cannot be read';
}