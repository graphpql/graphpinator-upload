<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Exception;

use Graphpinator\Exception\GraphpinatorBase;

abstract class UploadError extends GraphpinatorBase
{
    #[\Override]
    public function isOutputable() : bool
    {
        return true;
    }
}
