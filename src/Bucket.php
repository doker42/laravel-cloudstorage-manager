<?php

namespace Doker42\CloudStorageManager;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;

class Bucket
{

    private StorageClient $storage;
    public  \Google\Cloud\Storage\Bucket $bucket;


    public function __construct(string $bucketName)
    {
        $this->storage = new StorageClient();
        $this->bucket  = $this->storage->bucket($bucketName);
    }


    /**
     * @param $fileName
     * @return string|null
     */
    public function getContent($fileName): ?string
    {
        if (empty($fileName)) {
            return null;
        }
        $object = $this->bucket->object($fileName);

        if (!$object->exists()) {
            return null;
        }
        return $object->downloadAsStream()->getContents();
    }


    /**
     * @param $file
     * @param $fileName
     * @return void
     */
    public function put($file, $fileName): void
    {
        $this->bucket->upload(fopen($file, 'r'), ['name' => $fileName]);
    }


    /**
     * @param $content
     * @param $fileName
     * @return void
     */
    public function putContent($content, $fileName): void
    {
        $this->bucket->upload($content, ['name' => $fileName]);
    }


    /**
     * @param string $filename
     * @return bool
     */
    public function ifFile(string $filename): bool
    {
        return $this->bucket->object($filename)->exists();
    }


    /**
     * @param $filename
     * @return void
     */
    public function delete($filename): void
    {
        $this->bucket->object($filename)?->delete();
    }


    /**
     * @param $filename
     * @param $min
     * @param array $options
     * @return string|null
     */
    public function urlSign($filename, $min = null, array $options = []): string|null
    {

        $min = $min ? "$min min" : '300 min';

        try {

            $object = $this->bucket->object($filename);

            $urlOptions = ['version' => 'v4'];

            if (count($options)) {
                $urlOptions = array_merge($urlOptions, $options);
            }

            return $object->signedUrl(
                new \DateTime($min),
                $urlOptions
            );

        } catch (\Exception $e) {

            Log::error('Error get sign_url : ' . $e->getMessage());
            return null;
        }
    }


    /**
     * @param string $objectName
     * @return string
     */
    public function getSignUrl(string $objectName): string
    {
        $object = $this->bucket->object($objectName);

        return $object->signedUrl(
            new \DateTime('120 min'),
            ['version' => 'v4']
        );
    }


    /**
     * @param string $objectName
     * @return array
     */
    public function postSignUrl(string $objectName): array
    {
        return $this->bucket->generateSignedPostPolicyV4(
            $objectName,
            new \DateTime('60 min'),
            [
                'save-as-name' => $objectName,

                'fields' => [
                    'x-goog-meta-test' => 'data'
                ]
            ]
        );
    }


    /**
     * @param string $objectName
     * @return string
     */
    public function deleteSignUrl(string $objectName): string
    {
        $object = $this->bucket->object($objectName);

        return  $object->signedUrl(
            new \DateTime('120 min'),
            [
                'method' => 'DELETE',
                'contentType' => 'application/octet-stream',
                'version' => 'v4',
            ]
        );
    }
}
