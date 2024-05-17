<?php

namespace Doker42\CloudStorageManager;

use Illuminate\Support\Str;

trait BucketTrait
{
    /**
     * @param $file
     * @return string
     */
    public static function createFileName($file): string
    {
        $ext = $file->getClientOriginalExtension();
        $hash = md5(Str::random(10) . time());

        return $hash . '.' . $ext;
    }

    /**
     * @param $original_file_name
     * @return string
     */
    public static function createFileNameByOriginal($original_file_name): string
    {
        $arr  = explode('.', $original_file_name);
        $ext  = end($arr);
        $hash = md5(Str::random(10) . time());

        return $hash . '.' . $ext;
    }


    /**
     * @param string $filename
     * @param string $extension
     * @return bool
     */
    public static function ifFileExstentionIs(string $filename, string $extension): bool
    {
        $ext = explode('.', $filename);

        return isset($ext[1]) && $ext[1] == $extension;
    }


    /**
     * @param $content
     * @return array
     */
    public static function getContentHeader($content): array
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);

        return [
            'Content-Type' => $fileInfo->buffer($content),
        ];
    }


}
