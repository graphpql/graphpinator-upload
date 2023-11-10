<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

interface FileProvider
{
    public function getMap() : ?\Infinityloop\Utils\Json;

    public function getFile(string $key) : \Psr\Http\Message\UploadedFileInterface;
}
