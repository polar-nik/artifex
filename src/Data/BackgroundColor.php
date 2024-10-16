<?php namespace PolarNik\Artifex\Data;

use PolarNik\Artifex\Interfaces\Image as ImageInterface;
use PolarNik\Artifex\Interfaces\BackgroundColor as BackgroundColorInterface;

class BackgroundColor implements BackgroundColorInterface
{
    private array $top = [];
    private array $bottom = [];
    private array $left = [];
    private array $right = [];

    private ImageInterface $image;

    public function __construct(ImageInterface $image)
    {
        $this->image = $image;

        [$topEntry, $botEntry] = $this->sort($image->getWidth(), $image->getHeight());

        $this->setTop($topEntry);
        $this->setBottom($botEntry);

        [$leftEntry, $rightEntry] = $this->sort($image->getHeight(), $image->getWidth(), false);

        $this->setLeft($leftEntry);
        $this->setRight($rightEntry);
    }

    private function sort(int $for, int $y, bool $is_width = true): array
    {
        $firstEntry = $secondEntry = [];
        $resource = $this->image->getResource();

        for ($i = 0; $i < $for; $i++) {
            $color = imagecolorat($resource, $is_width ? $i : 0, $is_width ? 0 : $i);
            $firstEntry[$color] = (isset($firstEntry[$color])) ? $firstEntry[$color] + 1 : 1;

            $color = imagecolorat($resource, $is_width ? $i : $y - 1, $is_width ? $y - 1 : $i);
            $secondEntry[$color] = (isset($secondEntry[$color])) ? $secondEntry[$color] + 1 : 1;
        }

        arsort($firstEntry);
        arsort($secondEntry);

        return [$firstEntry, $secondEntry];
    }

    private function setTop(array $data)
    {
        $color = key($data);
        $this->top = [
            'color' => $color,
            'is_defined' => (100 * $data[$color] / $this->image->getWidth()) > 45,
        ];
    }

    private function setBottom(array $data)
    {
        $color = key($data);
        $this->bottom = [
            'color' => $color,
            'is_defined' => (100 * $data[$color] / $this->image->getWidth()) > 45,
        ];
    }

    private function setLeft(array $data)
    {
        $color = key($data);
        $this->left = [
            'color' => $color,
            'is_defined' => (100 * $data[$color] / $this->image->getHeight()) > 45,
        ];
    }

    private function setRight(array $data)
    {
        $color = key($data);
        $this->right = [
            'color' => $color,
            'is_defined' => (100 * $data[$color] / $this->image->getHeight()) > 45,
        ];
    }

    public function topColor()
    {
        return $this->top['color'];
    }

    public function bottomColor()
    {
        return $this->bottom['color'];
    }

    public function leftColor()
    {
        return $this->left['color'];
    }

    public function rightColor()
    {
        return $this->right['color'];
    }

    public function topDefined()
    {
        return $this->top['is_defined'];
    }

    public function bottomDefined()
    {
        return $this->bottom['is_defined'];
    }

    public function leftDefined()
    {
        return $this->left['is_defined'];
    }

    public function rightDefined()
    {
        return $this->right['is_defined'];
    }

    public function defineAll(bool $bool)
    {
        $this->top['is_defined'] = $bool;
        $this->bottom['is_defined'] = $bool;
        $this->left['is_defined'] = $bool;
        $this->right['is_defined'] = $bool;
    }
}