<?php namespace PolarNik\Artifex\Exceptions;

use Exception;

class GdLibraryNotInstalled extends Exception
{
    protected $message = 'GD library not installed';
}