<?php

namespace MCAG\InfrastrutturaIT;

interface ICloudStorage
{
    /**
     * @param string $fileName
     * @param string $content
     * @return string URL of uploaded file
     */
    public function upload(string $fileName, string $content): string;

    /**
     * @param string $uuid
     * @return mixed FileBlob
     */
    public function download(string $uuid);

    public function delete(string $uuid): void;
}


