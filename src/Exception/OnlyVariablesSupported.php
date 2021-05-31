<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

final class OnlyVariablesSupported extends \Graphpinator\Upload\Exception\UploadError
{
    public const MESSAGE = 'Files must be passed to variables.';
}
