<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

use Graphpinator\Typesystem\Attribute\Description;
use Graphpinator\Typesystem\ScalarType;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @extends ScalarType<UploadedFileInterface>
 */
#[Description(<<<'NOWDOC'
    Upload type - represents file which was send to server.
    By GraphQL viewpoint it is scalar type, but it must be used as input only.;
    NOWDOC)]
final class UploadType extends ScalarType
{
    protected const NAME = 'Upload';

    public function __construct()
    {
        parent::__construct();

        $this->setSpecifiedBy('https://github.com/jaydenseric/graphql-multipart-request-spec');
    }

    #[\Override]
    public function validateAndCoerceInput(mixed $rawValue) : ?UploadedFileInterface
    {
        return $rawValue instanceof UploadedFileInterface
            ? $rawValue
            : null;
    }

    #[\Override]
    public function coerceOutput(mixed $rawValue) : string|int|float|bool
    {
        throw new \LogicException('Upload type can not be used as output.');
    }
}
