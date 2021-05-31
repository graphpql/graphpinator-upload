<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

final class ConflictingMap extends \Graphpinator\Upload\Exception\UploadError
{
    public const MESSAGE = 'Upload map is in conflict with other value.';
}
