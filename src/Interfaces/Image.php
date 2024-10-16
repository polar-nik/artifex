<?php namespace PolarNik\Artifex\Interfaces;

interface Image
{
    public function __construct(string $file);

    public function getFile(): string;
    public function getHeight(): int;
    public function getType(): int;
    public function getResource();
    public function getWidth(): int;

    public function update($resource, ?int $width = null, ?int $height = null);
    public function updateWidth(int $width): void;
    public function updateHeight(int $height): void;
}