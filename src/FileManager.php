<?php

namespace Doker42\CloudStorageManager;


class FileManager
{
    use BucketTrait;

    protected Bucket $bucket;

    public function __construct(Bucket $bucket)
    {
        $this->bucket = $bucket;
    }


    /**
     * @param $file
     * @return string|null
     */
    public function store($file): string|null
    {
        $filename = self::createFileName($file);
        return $this->upload($file, $filename) ? $filename : null;
    }


    /**
     * @param $file
     * @param $filename
     * @return bool
     */
    protected function upload($file, $filename): bool
    {
        $filePath = $file->getRealPath();
        $this->bucket->put($filePath, $filename);
        return $this->bucket->ifFile($filename);
    }


    /**
     * @param $file
     * @param $filename
     * @return bool
     */
    public function uploadContent($file, $filename): bool
    {
        $filePath = $file->getRealPath();
        $this->bucket->putContent($filePath, $filename);
        return $this->bucket->ifFile($filename);
    }


    /**
     * @param $filename
     * @return void
     */
    public function delete($filename): void
    {
        if ($this->bucket->ifFile($filename)) {
            $this->bucket->delete($filename);
        }
    }
}