<?php namespace PolarNik\Artifex\Interfaces;

interface BackgroundColor
{
    public function __construct(Image $image);

    public function topColor();
    public function bottomColor();
    public function leftColor();
    public function rightColor();

    public function topDefined();
    public function bottomDefined();
    public function leftDefined();
    public function rightDefined();

    public function defineAll(bool $bool);
}