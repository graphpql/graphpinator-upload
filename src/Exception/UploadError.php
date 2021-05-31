<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

abstract class UploadError extends \Graphpinator\Exception\GraphpinatorBase
{
    public function isOutputable() : bool
    {
        return true;
    }
}
