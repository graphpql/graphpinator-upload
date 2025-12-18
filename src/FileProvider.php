<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

use Infinityloop\Utils\Json;
use Psr\Http\Message\UploadedFileInterface;

interface FileProvider
{
    public function getMap() : ?Json;

    public function getFile(string $key) : UploadedFileInterface;
}
