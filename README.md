# Artifex

Artifex - удобная обёртка над GD-библиотекой для работы с изображениями.

Она умеет изменять размер, вырезать, изменять формат и сохранять изображения.

## Документация

Все доступные методы доступны в [Wiki](https://github.com/polar-nik/artifex/wiki)

## Примеры

Обрезать, добавить водяной знак и сохранить изображение:

```php
$image = new \PolarNik\Artifex\Artifex('path/to/image.jpg');
$image->cut(512, 512); // Обрезать изображение
$image->watermark('path/to/watermark.png'); // Добавить водяной знак

$image->save('path/to/image-image-with-watermark.jpg'); // Сохранить изображение
```

Вывести превью изображения:

```php
$image = new \PolarNik\Artifex\Artifex('path/to/another-image.jpg');
$image->thumb(256, 256); // Создать превью

$image->output('path/to/image-image-with-watermark.jpg'); // Вывести изображение
```