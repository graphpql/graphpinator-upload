<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

final class InvalidMap extends \Graphpinator\Upload\Exception\UploadError
{
    public const MESSAGE = 'Invalid map - invalid file map provided in multipart request.';
}
