<?php

namespace Doker42\CloudStorageManager;

use Illuminate\Support\Facades\Storage;

class ImageManager extends FileManager
{
    public const EXT_SVG = 'svg';
    public const EXT_PNG = 'png';
    public const IMAGE_ORIGIN = 'origin';
    public const IMAGE_BIG    = 'big';
    public const IMAGE_SMALL  = 'small';
    public const EXT_ACCESSED = [
        self::EXT_PNG,
        self::EXT_SVG
    ];


    /**
     * @param $file
     * @param array $sizes
     * @return string|null
     */
    public function store($file, array $sizes = []): string|null
    {
        $filename = self::createFileName($file);
        $filename = self::upload($file, $filename) ? $filename : null;

        if (!$this->ifImageExtension($filename, self::EXT_SVG) && $filename && $sizes) {
            $res = $this->generateThumbnails($file, $filename, $sizes);

            return count($res) == count($sizes) ? $filename : null;
        }

        return $filename;
    }


    /**
     * @param string $filename
     * @param string $extension
     * @return bool
     */
    public function ifImageExtension(string $filename, string $extension): bool
    {
        if (!in_array($extension, self::EXT_ACCESSED)) {
            return false;
        }

        $ext = explode('.', $filename);
        return isset($ext[1]) && $ext[1] == $extension;
    }


    /**
     * @param $image
     * @param string $filename
     * @param array $sizes
     * @return array
     */
    private function generateThumbnails($image, string $filename, array $sizes): array
    {
        $res = [];
        /* reduce and store */
        foreach($sizes as $key => $value) {
            $cover_filename = $key . '_' . $filename;
            $res[] = $this->reduceAndStore($image, $cover_filename, $value);
        }

        return $res;
    }


    /**
     * @param $image
     * @param string $filename
     * @param array $size
     * @return string|null
     */
    public function reduceAndStore($image, string $filename, array $size): string|null
    {
        $image = \Intervention\Image\Facades\Image::make($image->path());

        $image->resize($size['width'], null, function ($const) {
            $const->aspectRatio();
        });
        $image->stream();

        Storage::disk('temp')->put($filename, $image, 'public' );
        $path_save = '/tmp/' . $filename;

        if (Storage::disk('temp')->exists($filename)){
            /* upload to bucket */
            $this->bucket->put($path_save, $filename);
            /* remove from local Storage */
            Storage::disk('temp')->delete($filename);
        }

        return $this->bucket->ifFile($filename) ? $filename : null;
    }


    /**
     * @param string $name
     * @param array|null $sizes
     * @return null[]
     */
    public function getThumbnails(string $name, array $sizes = null): array
    {
        $res = [
            'origin' => $name ? $this->bucket->urlSign($name) : null
        ];

        if ($sizes) {
            foreach ($sizes as $size) {
                $res += [
                    $size => $name
                        ? ($this->ifImageExtension($name, self::EXT_SVG) ?  $this->bucket->urlSign($name) : $this->bucket->urlSign($size . '_' . $name))
                        : null
                ];
            }
        }

        return $res;
    }


    /**
     * @param string $fileName
     * @param string $size
     * @return string|null
     */
    public function getThumbnail(string $fileName, string $size): string|null
    {
        return  match($size) {

            self::IMAGE_ORIGIN => $this->bucket->urlSign($fileName),

            self::IMAGE_BIG    => $fileName
                ? ($this->ifImageExtension($fileName, self::EXT_SVG)
                    ? $this->bucket->urlSign($fileName)
                    : $this->bucket->urlSign(self::IMAGE_BIG . '_' . $fileName))
                : null,

            self::IMAGE_SMALL  => $fileName
                ? (self::ifImageExtension($fileName, self::EXT_SVG)
                    ? $this->bucket->urlSign($fileName)
                    : $this->bucket->urlSign(self::IMAGE_SMALL . '_' . $fileName))
                : null
        };
    }


    /**
     *  for Logo & Favicon
     *
     * @param $name
     * @param array|null $sizes
     * @return null[]|string[]
     */
    public function getThumbnailsContent($name, array $sizes=null): array
    {
        $res = [
            'origin' => $name ?: null
        ];

        if ($sizes) {
            foreach ($sizes as $size) {
                $res += [
                    $size => $name ? $size . '_' . $name : null
                ];
            }
        }

        return $res;
    }
}