<?php namespace PolarNik\Artifex\Data;

use PolarNik\Artifex\Exceptions\EmptyImagesizeDataException;
use PolarNik\Artifex\Exceptions\FileNotFoundException;
use PolarNik\Artifex\Exceptions\UnsupportedFileFormatException;
use PolarNik\Artifex\Interfaces\Image as ImageInterface;

class Image implements ImageInterface
{
    private string $file;

    /**
     * @var false|resource
     */
    private $resource;

    private int $height;
    private int $type;
    private int $width;

    private array $backgroundColors;

    /**
     * @throws FileNotFoundException
     * @throws EmptyImagesizeDataException
     * @throws UnsupportedFileFormatException
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        $this->fillDefaultData(
            $this->handleFile($file)
        );

        $this->constructImageFromFile();
    }

    /**
     * @throws FileNotFoundException
     * @throws EmptyImagesizeDataException
     */
    private function handleFile(string $file): array
    {
        if (!is_file($file)) {
            throw new FileNotFoundException();
        }

        $imageData = getimagesize($file);

        if (empty($imageData)) {
            throw new EmptyImagesizeDataException();
        }

        return $imageData;
    }

    private function fillDefaultData(array $imageData)
    {
        $this->height = $imageData[1];
        $this->type = $imageData[2];
        $this->width = $imageData[0];
    }

    /**
     * @throws UnsupportedFileFormatException
     */
    private function constructImageFromFile()
    {
        switch ($this->type) {
            case IMAGETYPE_GIF:
                $this->resource = imageCreateFromGif($this->file);
                break;
            case IMAGETYPE_JPEG:
                $this->resource = imageCreateFromJpeg($this->file);
                break;
            case IMAGETYPE_PNG:
                $this->resource = imageCreateFromPng($this->file);
                $transparent = imagecolorallocatealpha($this->resource, 0, 0, 0, 127);
                imagefill($this->resource, 0, 0, $transparent);
                imageAlphaBlending($this->resource, true);
                imageSaveAlpha($this->resource, true);
                break;
            case IMAGETYPE_WEBP:
                $this->resource = imageCreatefromWebp($this->file);
                break;
            default:
                throw new UnsupportedFileFormatException();
        }
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return false|resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    public function update($resource, ?int $width = null, ?int $height = null): void
    {
        $this->resource = $resource;

        if (!empty($width)) {
            $this->updateWidth($width);
        }

        if (!empty($height)) {
            $this->updateHeight($height);
        }
    }

    public function updateWidth(int $width): void
    {
        $this->width = $width;
    }

    public function updateHeight(int $height): void
    {
        $this->height = $height;
    }
}