<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

final class UninitializedVariable extends \Graphpinator\Upload\Exception\UploadError
{
    public const MESSAGE = 'Variable for Upload must be initialized.';
}
