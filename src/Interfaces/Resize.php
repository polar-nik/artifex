<?php namespace PolarNik\Artifex\Interfaces;

interface Resize
{
    public function __construct(Image $image);

    public function getX(): int;
    public function getY(): int;
    public function getWidth(): int;
    public function getHeight(): int;
}