<?php namespace PolarNik\Artifex\Data;

use PolarNik\Artifex\Interfaces\Resize as ResizeInterface;
use PolarNik\Artifex\Interfaces\Image as ImageInterface;

class Resize implements ResizeInterface
{
    private int $x;
    private int $y;
    private int $width;
    private int $height;

    private ImageInterface $image;

    public function __construct(ImageInterface $image)
    {
        $this->image = $image;
    }

    public function generate(int $width, int $height, int $calculatedWidth, int $calculatedHeight): Resize
    {
        if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
            $this->x = ceil(($width - $this->image->getWidth()) / 2);
            $this->y = ceil(($height - $this->image->getHeight()) / 2);
            $this->width = $this->image->getWidth();
            $this->height = $this->image->getHeight();
        } elseif ($width >= $this->image->getWidth()) {
            $this->x = ceil(($width - $calculatedWidth) / 2);
            $this->y = 0;
            $this->width = ceil($height / ($this->image->getHeight() / $this->image->getWidth()));
            $this->height = $height;
        } elseif ($height >= $this->image->getHeight()) {
            $this->x = 0;
            $this->y = ceil(($height - $calculatedHeight) / 2);
            $this->width = $width;
            $this->height = ceil($width / ($this->image->getWidth() / $this->image->getHeight()));
        } elseif ($calculatedWidth < $width) {
            $this->x = ceil(($width - $calculatedWidth) / 2);
            $this->y = 0;
            $this->width = $calculatedWidth;
            $this->height = $height;
        } else {
            $this->x = 0;
            $this->y = ceil(($height - $calculatedHeight) / 2);
            $this->width = $width;
            $this->height = $calculatedHeight;
        }

        return $this;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setX(int $int): void
    {
        $this->x = $int;
    }

    public function setY(int $int): void
    {
        $this->y = $int;
    }

    public function setWidth(int $int): void
    {
        $this->width = $int;
    }

    public function setHeight(int $int): void
    {
        $this->height = $int;
    }

}