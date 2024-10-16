<?php namespace PolarNik\Artifex;

use PolarNik\Artifex\Data\BackgroundColor;
use PolarNik\Artifex\Data\Image;
use PolarNik\Artifex\Data\Resize;
use PolarNik\Artifex\Exceptions\EmptyImagesizeDataException;
use PolarNik\Artifex\Exceptions\EmptySizeException;
use PolarNik\Artifex\Exceptions\FileNotFoundException;
use PolarNik\Artifex\Exceptions\GdLibraryNotInstalled;
use PolarNik\Artifex\Exceptions\UnsupportedFileFormatException;
use PolarNik\Artifex\Interfaces\BackgroundColor as BackgroundColorInterface;
use PolarNik\Artifex\Interfaces\Image as ImageInterface;
use PolarNik\Artifex\Interfaces\Resize as ResizeInterface;
use stdClass;

/**
 * Artifex
 *
 * @version 1.0.0
 */
class Artifex
{
    public ?ImageInterface $image = null;
    public ?ResizeInterface $resize = null;
    public ?BackgroundColorInterface $backgroundColors = null;

    /**
     * @throws GdLibraryNotInstalled
     * @throws EmptyImagesizeDataException
     * @throws FileNotFoundException
     * @throws UnsupportedFileFormatException
     */
    public function __construct(string $filename)
    {
        if (!extension_loaded('gd')) {
            throw new GdLibraryNotInstalled();
        }

        $this->image = new Image($filename);
        $this->resize = new Resize($this->image);
        $this->backgroundColors = new BackgroundColor($this->image);
    }

    /**
     * Уменьшить изображение, если оно больше указанных размеров.
     *
     * @throws EmptySizeException
     */
    public function reduce(?int $max_width, ?int $max_height = null)
    {
        if (empty($max_width) && empty($max_height)) {
            throw new EmptySizeException();
        } elseif (empty($max_width)) {
            $max_width = ceil($max_height / ($this->image->getHeight() / $this->image->getWidth()));
        } elseif (empty($max_height)) {
            $max_height = ceil($max_width / ($this->image->getWidth() / $this->image->getHeight()));
        }

        if ($this->image->getWidth() > $max_width || $this->image->getHeight() > $max_height) {
            $width = ceil($max_height / ($this->image->getHeight() / $this->image->getWidth()));
            $height = ceil($max_width / ($this->image->getWidth() / $this->image->getHeight()));

            $width = $width > $max_width ? $max_width : $max_height;

            $this->resize($width, $height);
        }
    }

    /**
     * Изменение размера изображения
     *
     * @param int|null $width
     * @param int|null $height
     * @param array{0: int, 1: int, 2: int} $bg [red, green, blue]
     *
     * @throws EmptySizeException
     */
    public function resize(?int $width, ?int $height = null, array $bg = [])
    {
        [$width, $height, $tw, $th] = $this->handleSizes($width, $height);

        $blankImage = $this->createBlankImage($width, $height, $bg);

        $resizeData = $this->resize->generate($width, $height, $tw, $th);

        imageCopyResampled(
            $blankImage,
            $this->image->getResource(),
            $resizeData->getX(),
            $resizeData->getY(),
            0,
            0,
            $resizeData->getWidth(),
            $resizeData->getHeight(),
            $this->image->getWidth(),
            $this->image->getHeight(),
        );

        $this->image->update($blankImage, $width, $height);

        unset($blankImage);
    }

    /**
     * Вырезать часть изображения
     *
     * @throws EmptySizeException
     */
    public function crop(?int $x, ?int $y, ?int $width, ?int $height = null, array $bg = [])
    {
        if (empty($width) && empty($height)) {
            throw new EmptySizeException();
        }

        if (empty($height)) {
            $height = $width;
        }

        if (empty($width)) {
            $width = $height;
        }

        $blankImage = $this->createBlankImage($width, $height, $bg);

        imageCopyResampled(
            $blankImage,
            $this->image->getResource(),
            0,
            0,
            $x,
            $y,
            $this->image->getWidth(),
            $this->image->getHeight(),
            $this->image->getWidth(),
            $this->image->getHeight()
        );

        $this->image->update($blankImage, $width, $height);

        unset($blankImage);
    }

    /**
     * Вырезать центральную часть изображения
     *
     * @throws EmptySizeException
     */
    public function cut(?int $width, ?int $height = null, array $bg = []): bool
    {
        [$width, $height, $tw, $th] = $this->handleSizes($width, $height);

        if ($this->image->getWidth() != $width && $this->image->getHeight() != $height) {
            if ($this->image->getWidth() === $this->image->getHeight()) {
                // Источник - квадратная фотка
                if ($width === $height) {
                    // Превью - квадратная.
                    $this->resize($width, $height, $bg);
                } elseif ($width > $height) {
                    // Превью - горизонтальная.
                    $this->resize($width, $width, $bg);
                    $this->crop(0, ceil(($this->image->getHeight() - $height) / 2), $width, $height, $bg);
                } else {
                    // Превью - вертикальная.
                    $this->resize($height, $height, $bg);
                    $this->crop(ceil(($this->image->getWidth() - $width) / 2), null, $width, $height, $bg);
                }
            } elseif ($this->image->getWidth() > $this->image->getHeight()) {
                // Источник - горизонтальная фотка
                if ($width === $height) {
                    // Превью - квадратная.
                    $this->resize(null, $height, $bg);
                    $this->crop(ceil(($this->image->getWidth() - $width) / 2), 0, $width, $height, $bg);
                } elseif ($width > $height) {
                    // Превью - горизонтальная.
                    if ($width <= $tw) {
                        $this->resize(null, $height, $bg);
                        $this->crop(ceil(($this->image->getWidth() - $width) / 2), 0, $width, $height, $bg);
                    } else {
                        $this->resize($width + 1, 0, $bg);
                        $this->crop(0, ceil(($this->image->getHeight() - $height) / 2), $width, $height, $bg);
                    }
                } else {
                    // Превью - вертикальная.
                    $this->resize(0, $height, $bg);
                    $this->crop(ceil(($this->image->getWidth() - $width) / 2), 0, $width, $height, $bg);
                }
            } else {
                // Источник - вертикальная фотка
                if ($width === $height) {
                    // Превью - квадратная.
                    $this->resize($width, 0, $bg);
                    $this->crop(
                        (ceil($this->image->getWidth() - $width) / 2),
                        ceil((($this->image->getHeight() - $height) / 2) / 2),
                        $width,
                        $height,
                        $bg
                    );
                } elseif ($width > $height) {
                    // Превью - горизонтальная.
                    $this->resize($width, 0, $bg);
                    $this->crop(0, ceil((($this->image->getHeight() - $height) / 2) / 3), $width, $height, $bg);
                } else {
                    // Превью - вертикальная.
                    if ($tw > $width) {
                        $this->resize(0, $height, $bg);
                    } else {
                        $this->resize(0, $th, $bg);
                    }

                    $this->crop(ceil(($this->image->getWidth() - $width) / 2), 0, $width, $height, $bg);
                }
            }

            $this->image->updateWidth($width);
            $this->image->updateHeight($height);
        }

        return true;
    }

    /**
     * Превью изображения
     *
     * @throws EmptySizeException
     */
    public function thumb(?int $width, ?int $height = null, array $bg = []): bool
    {
        [$width, $height, $tw, $th] = $this->handleSizes($width, $height);

        if ($this->image->getWidth() != $width && $this->image->getHeight() != $height) {
            $bgColors = $this->backgroundColors;
            $blankImage = $this->createBlankImage($width, $height);

            if ($bgColors->topDefined() || $bgColors->bottomDefined() || $bgColors->rightDefined() || $bgColors->leftDefined()) {
                $data = new Resize($this->image);
                
                if (!$bgColors->topDefined() && $bgColors->rightDefined() && $bgColors->bottomDefined() && $bgColors->leftDefined()) {
                    // top
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX(ceil(($width - $this->image->getWidth()) / 2));
                        $data->setY(0);
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX(ceil(($width - ($height / ($this->image->getHeight() / $this->image->getWidth()))) / 2));
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX(ceil(($width - $tw) / 2));
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif (!$bgColors->topDefined() && $bgColors->rightDefined() && $bgColors->bottomDefined() && !$bgColors->leftDefined()) {
                    // top-left
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif (!$bgColors->topDefined() && !$bgColors->rightDefined() && $bgColors->bottomDefined() && $bgColors->leftDefined()) {
                    // top-right
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX($width - $this->image->getWidth());
                        $data->setY(0);
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif ($bgColors->topDefined() && $bgColors->rightDefined() && !$bgColors->bottomDefined() && !$bgColors->leftDefined()) {
                    // bottom-left
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY($height - $this->image->getHeight());
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif ($bgColors->topDefined() && !$bgColors->rightDefined() && !$bgColors->bottomDefined() && $bgColors->leftDefined()) {
                    // bottom-right
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX($width - $this->image->getWidth());
                        $data->setY($height - $this->image->getHeight());
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX($width - $tw);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif ($bgColors->topDefined() && !$bgColors->rightDefined() && $bgColors->bottomDefined() && $bgColors->leftDefined() && ($bgColors->topColor() == $bgColors->bottomColor() && $bgColors->bottomColor() == $bgColors->leftColor())) {
                    // right
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX($width - $this->image->getWidth());
                        $data->setY(ceil(($height - $this->image->getHeight()) / 2));
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX($width - $tw);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY(ceil(($height - $th) / 2));
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif ($bgColors->topDefined() && $bgColors->rightDefined() && !$bgColors->bottomDefined() && $bgColors->leftDefined()) {
                    // bottom
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX(ceil(($width - $this->image->getWidth()) / 2));
                        $data->setY($height - $this->image->getHeight());
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX(ceil(($width - ($height / ($this->image->getHeight() / $this->image->getWidth()))) / 2));
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX(ceil(($width - $tw) / 2));
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY($height - $th);
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif ($bgColors->topDefined() && $bgColors->rightDefined() && $bgColors->bottomDefined() && !$bgColors->leftDefined()) {
                    // left
                    if ($width >= $this->image->getWidth() && $height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(ceil(($height - $this->image->getHeight()) / 2));
                        $data->setWidth($this->image->getWidth());
                        $data->setHeight($this->image->getHeight());
                    } elseif ($width >= $this->image->getWidth()) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth(ceil($height / ($this->image->getHeight() / $this->image->getWidth())));
                        $data->setHeight($height);
                    } elseif ($height >= $this->image->getHeight()) {
                        $data->setX(0);
                        $data->setY(ceil(($height - $th) / 2));
                        $data->setWidth($width);
                        $data->setHeight(ceil($width / ($this->image->getWidth() / $this->image->getHeight())));
                    } elseif ($tw < $width) {
                        $data->setX(0);
                        $data->setY(0);
                        $data->setWidth($tw);
                        $data->setHeight($height);
                    } else {
                        $data->setX(0);
                        $data->setY(ceil(($height - $th) / 2));
                        $data->setWidth($width);
                        $data->setHeight($th);
                    }
                } elseif (($bgColors->topDefined() && $bgColors->bottomDefined()) || ($bgColors->rightDefined() && $bgColors->leftDefined())) {
                    // more
                    $data = $this->resize->generate($width, $height, $tw, $th);
                } else {
                    return $this->cut($width, $height);
                }

                imageCopyResampled(
                    $blankImage,
                    $this->image->getResource(),
                    $data->getX(),
                    $data->getY(),
                    0,
                    0,
                    $data->getWidth(),
                    $data->getHeight(),
                    $this->image->getWidth(),
                    $this->image->getHeight(),
                );

                $this->image->update($blankImage);

                unset($blankImage);

                return true;
            } else {
                return $this->cut($width, $height, $bg);
            }
        }

        return false;
    }

    /**
     * Поворот изображения
     *
     * @throws EmptySizeException
     */
    public function rotate(int $deg, array $bg = [])
    {
        $resource = $this->image->getResource();
        $transparent = imageColorAllocateAlpha($resource, 0, 0, 0, 127);
        $rotated = imagerotate($resource, $deg, $transparent);
        $width = imagesx($rotated);
        $height = imagesy($rotated);
        $blankImage = $this->createBlankImage($width, $height, $bg);

        imagecopy(
            $blankImage,
            $rotated,
            0,
            0,
            0,
            0,
            $width,
            $height
        );

        $this->image->update($blankImage, $width, $height);
    }

    /**
     * Изменить прозрачность изображения
     */
    public function opacity(int $percent)
    {
        imagealphablending($this->image->getResource(), false);

        imagefilter($this->image->getResource(), IMG_FILTER_COLORIZE, 0,0,0,127 * (1 - ($percent / 100)));
    }

    /** Фикс для цветов Webp */
    public function fixWebpColors()
    {
        $imageWidth = imagesx($this->image->getResource());
        $imageHeight = imagesy($this->image->getResource());
        $blankImage = imagecreatetruecolor($imageWidth, $imageHeight);
        $blackColor = imagecolorallocate($blankImage, 255, 255, 255);
        imagefill($blankImage, 0, 0, $blackColor);

        for ($y = 0; $y < $imageHeight; $y++) {
            for ($x = 0; $x < $imageWidth; $x++) {
                $rgb_old = imagecolorat($this->image->getResource(), $x, $y);
                $r = ($rgb_old >> 24) & 0xFF;
                $g = ($rgb_old >> 16) & 0xFF;
                $b = ($rgb_old >> 8) & 0xFF;
                $pixelColor = imagecolorallocate($blankImage, $r, $g, $b);
                imagesetpixel($blankImage, $x, $y, $pixelColor);
            }
        }

        $this->image->update($blankImage);
    }

    /**
     * Наложить "водяной знак" на изображение
     */
    public function watermark(string $file, $position = 'center', $transparency = 70)
    {
        $watermark = new static($file);

        switch ($position) {
            case 'top':
                $x = ceil(($this->image->getWidth() - $watermark->image->getWidth()) / 2);
                $y = 0;
                break;
            case 'top-left':
                $x = 0;
                $y = 0;
                break;
            case 'top-right':
                $x = ceil($this->image->getWidth() - $watermark->image->getWidth());
                $y = 0;
                break;
            case 'left':
                $x = 0;
                $y = ceil(($this->image->getHeight() - $watermark->image->getHeight()) / 2);
                break;
            case 'right':
                $x = ceil($this->image->getWidth() - $watermark->image->getWidth());
                $y = ceil(($this->image->getHeight() - $watermark->image->getHeight()) / 2);
                break;
            case 'bottom':
                $x = ceil(($this->image->getWidth() - $watermark->image->getWidth()) / 2);
                $y = ceil($this->image->getHeight() - $watermark->image->getHeight());
                break;
            case 'bottom-left':
                $x = 0;
                $y = ceil($this->image->getHeight() - $watermark->image->getHeight());
                break;
            case 'bottom-right':
                $x = ceil($this->image->getWidth() - $watermark->image->getWidth());
                $y = ceil($this->image->getHeight() - $watermark->image->getHeight());
                break;
            default:
                $x = ceil(($this->image->getWidth() - $watermark->image->getWidth()) / 2);
                $y = ceil(($this->image->getHeight() - $watermark->image->getHeight()) / 2);
                break;
        }

        $watermark->opacity($transparency);

        imagecopy(
            $this->image->getResource(),
            $watermark->image->getResource(), 
            $x,
            $y,
            0, 
            0,
            $watermark->image->getWidth(),
            $watermark->image->getHeight()
        );
    }

    /**
     * Вывести изображение в браузер.
     */
    public function output(int $quality = 100)
    {
        switch ($this->image->getType()) {
            case IMAGETYPE_GIF:
                header('Content-Type: image/gif');
                imageGif($this->image->getResource());
                break;
            case IMAGETYPE_JPEG:
                header('Content-Type: image/jpg');
                imagejpeg($this->image->getResource(), null, $quality);
                break;
            case IMAGETYPE_PNG:
                header('Content-Type: image/x-png');
                imagePng($this->image->getResource());
                break;
            case IMAGETYPE_WEBP:
                header('Content-Type: image/webp');
                imageWebp($this->image->getResource(), $quality);
                break;
        }

        exit;
    }

    /**
     * Сохранить изображение в файл.
     */
    public function save(?string $filename = null, $quality = 100): bool
    {
        if (empty($filename)) {
            $filename = $this->image->getFile();
        }

        switch ($this->image->getType()) {
            case IMAGETYPE_GIF : return $this->saveGif($filename);
            case IMAGETYPE_JPEG: return $this->saveJpg($filename, $quality);
            case IMAGETYPE_PNG : return $this->savePng($filename);
            case IMAGETYPE_WEBP: return $this->saveWebp($filename);
            default: return false;
        }
    }

    public function saveJpg(?string $filename = null, int $quality = 100): bool
    {
        if (empty($filename)) {
            $filename = $this->image->getFile();
        }

        return imageJpeg($this->image->getResource(), $filename, $quality);
    }

    public function savePng(?string $filename = null): bool
    {
        if (empty($filename)) {
            $filename = $this->image->getFile();
        }

        return imagePng($this->image->getResource(), $filename);
    }

    public function saveGif(?string $filename = null): bool
    {
        if (empty($filename)) {
            $filename = $this->image->getFile();
        }

        return imageGif($this->image->getResource(), $filename);
    }

    public function saveWebp(?string $filename = null, $quality = 100): bool
    {
        if (empty($filename)) {
            $filename = $this->image->getFile();
        }

        $this->fixWebpColors();

        return imageWebp($this->image->getResource(), $filename, $quality);
    }

    /**
     * Сохраняет и выводит изображение в браузер.
     */
    public function saveOutput(?string $filename = null, int $quality = 100)
    {
        $this->save($filename, $quality);

        $this->output();
    }

    public function saveJpgOutput(?string $filename, int $quality = 100)
    {
        $this->saveJpg($filename, $quality);

        $this->output();
    }

    public function savePngOutput(?string $filename = null)
    {
        $this->savePng($filename);

        $this->output();
    }

    public function saveGifOutput(?string $filename = null)
    {
        $this->saveGif($filename);

        $this->output();
    }

    public function saveWebpOutput(?string $filename, int $quality = 100)
    {
        $this->saveWebp($filename, $quality);

        $this->output();
    }

    /**
     * Удалить ресурс из памяти.
     *
     * Когда PHP завершает скрипт - он сам уничтожает все ресурсы.
     * Данный метод - должен использоваться до завершения скрипта,
     * для освобождения памяти. Для повседневных задач его
     * использование - бессмысленно.
     *
     * @deprecated php8
     */
    public function destroy()
    {
        imagedestroy($this->image->getResource());
    }

    /**
     * Заготовка, которая возвращает ресурс пустого изображения
     *
     * @return resource
     * @throws EmptySizeException
     */
    private function createBlankImage(int $width, int $height, array $bg = [])
    {
        $image = imagecreatetruecolor($width, $height);

        if (empty($bg)) {
            imagealphablending($image, true);
            imageSaveAlpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
            imagecolortransparent($image, $transparent);

            if (imageistruecolor($this->image->getResource())) {
                [, , $tw, $th] = $this->handleSizes($width, $height);
                $bgColors = $this->backgroundColors;

                $top_rgb = imagecolorsforindex($image, $bgColors->topColor());
                $right_rgb = imagecolorsforindex($image, $bgColors->rightColor());
                $bottom_rgb = imagecolorsforindex($image, $bgColors->bottomColor());
                $left_rgb = imagecolorsforindex($image, $bgColors->leftColor());

                if (!empty($top_rgb['alpha']) && !empty($right_rgb['alpha']) && !empty($bottom_rgb['alpha']) && !empty($left_rgb['alpha'])) {
                    $bgColors->defineAll(true);
                } elseif (empty($top_rgb['alpha']) || empty($right_rgb['alpha']) || empty($bottom_rgb['alpha']) || empty($left_rgb['alpha'])) {
                    if ($bgColors->topDefined() && $bgColors->bottomDefined() && !$bgColors->leftDefined() && !$bgColors->rightDefined()) {
                        if ($th < $height) {
                            imagefilledrectangle($image, 0, 0, $width - 1, $height / 2, $bgColors->topColor());
                            imagefilledrectangle($image, 0, $height / 2, $width - 1, $height - 1, $bgColors->bottomColor());
                        } else {
                            return $this->cut($width, $height, $bg);
                        }
                    } elseif ($bgColors->leftDefined() && $bgColors->rightDefined() && !$bgColors->topDefined() && !$bgColors->bottomDefined()) {
                        if ($tw < $width) {
                            imagefilledrectangle($image, 0, 0, $width / 2, $height - 1, $bgColors->leftColor());
                            imagefilledrectangle($image, $width / 2, 0, $width - 1, $height - 1, $bgColors->rightColor());
                        } else {
                            return $this->cut($width, $height);
                        }
                    } else {
                        if ($bgColors->topDefined()) {
                            imagefill($image, 0, 0, $bgColors->topColor());
                        } elseif ($bgColors->rightDefined()) {
                            imagefill($image, 0, 0, $bgColors->rightColor());
                        } elseif ($bgColors->bottomDefined()) {
                            imagefill($image, 0, 0, $bgColors->bottomColor());
                        } else {
                            imagefill($image, 0, 0, $bgColors->leftColor());
                        }
                    }
                }
            }
        } else {
            $bgColor = imagecolorallocate($image, $bg[0], $bg[1], $bg[2]);
            imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
        }

        return $image;
    }

    /**
     * Метод генерирует необходимые значения размеров.
     *
     * @param int|null $width
     * @param int|null $height
     *
     * @return array{width: int, height: int, calculatedWidth: int, calculatedHeight: int}
     * @throws EmptySizeException
     */
    private function handleSizes(?int $width, ?int $height = null): array
    {
        if (empty($width) && empty($height)) {
            throw new EmptySizeException();
        }

        if (empty($width)) {
            $width = ceil($height / ($this->image->getHeight() / $this->image->getWidth()));
        }

        if (empty($height)) {
            $height = ceil($width / ($this->image->getWidth() / $this->image->getHeight()));
        }

        $calculatedWidth = ceil($height / ($this->image->getHeight() / $this->image->getWidth()));
        $calculatedHeight = ceil($width / ($this->image->getWidth() / $this->image->getHeight()));

        return [$width, $height, $calculatedWidth, $calculatedHeight];
    }
}