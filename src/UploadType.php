<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

final class UploadType extends \Graphpinator\Typesystem\ScalarType
{
    protected const NAME = 'Upload';
    protected const DESCRIPTION = <<<'NOWDOC'
    Upload type - represents file which was send to server.
    By GraphQL viewpoint it is scalar type, but it must be used as input only.
    NOWDOC;

    public function __construct()
    {
        parent::__construct();

        $this->setSpecifiedBy('https://github.com/jaydenseric/graphql-multipart-request-spec');
    }

    public function validateNonNullValue(mixed $rawValue) : bool
    {
        return $rawValue instanceof \Psr\Http\Message\UploadedFileInterface;
    }
}
