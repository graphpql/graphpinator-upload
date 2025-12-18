<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

use Graphpinator\Module\Module;
use Graphpinator\Normalizer\FinalizedRequest;
use Graphpinator\Normalizer\NormalizedRequest;
use Graphpinator\Parser\ParsedRequest;
use Graphpinator\Request\Request;
use Graphpinator\Resolver\Result;
use Graphpinator\Upload\Exception\ConflictingMap;
use Graphpinator\Upload\Exception\InvalidMap;
use Graphpinator\Upload\Exception\OnlyVariablesSupported;
use Graphpinator\Upload\Exception\UninitializedVariable;
use Infinityloop\Utils\Json;
use Psr\Http\Message\UploadedFileInterface;

final readonly class UploadModule implements Module
{
    public function __construct(
        private FileProvider $fileProvider,
    )
    {
    }

    #[\Override]
    public function processRequest(Request $request) : Request
    {
        $variables = $request->variables;
        $map = $this->fileProvider->getMap()
            ?? Json::fromNative([]);

        foreach ($map as $fileKey => $locations) {
            $fileValue = $this->fileProvider->getFile((string) $fileKey);

            foreach ($locations as $location) {
                // Array reverse is done so we can use array_pop (O(1)) instead of array_shift (O(n))
                $keys = \array_reverse(\explode('.', $location));

                if (\array_pop($keys) !== 'variables') {
                    throw new OnlyVariablesSupported();
                }

                $variableName = \array_pop($keys);

                if (!\property_exists($variables, $variableName)) {
                    throw new UninitializedVariable();
                }

                $variableValue = $variables->{$variableName};
                $variables->{$variableName} = $this->insertFiles($keys, $variableValue, $fileValue);
            }
        }

        return $request;
    }

    #[\Override]
    public function processParsed(ParsedRequest $request) : ParsedRequest
    {
        return $request;
    }

    #[\Override]
    public function processNormalized(NormalizedRequest $request) : NormalizedRequest
    {
        return $request;
    }

    #[\Override]
    public function processFinalized(FinalizedRequest $request) : FinalizedRequest
    {
        return $request;
    }

    #[\Override]
    public function processResult(Result $result) : Result
    {
        return $result;
    }

    /**
     * @param list<string> $keys
     * @phpcs:ignore
     * @param string|int|float|bool|list<mixed>|\stdClass|null $currentValue
     * @param UploadedFileInterface $fileValue
     * @phpcs:ignore
     * @return list<mixed>|\stdClass|UploadedFileInterface
     */
    private function insertFiles(
        array &$keys,
        string|int|float|bool|array|\stdClass|null $currentValue,
        UploadedFileInterface $fileValue,
    ) : array|\stdClass|UploadedFileInterface
    {
        if (\is_scalar($currentValue)) {
            throw new ConflictingMap();
        }

        if (\count($keys) === 0) {
            if ($currentValue === null) {
                return $fileValue;
            }

            throw new InvalidMap();
        }

        $index = \array_pop($keys);

        if (\is_numeric($index)) {
            $index = (int) $index;

            if ($currentValue === null) {
                $currentValue = [];
            }

            if (\is_array($currentValue)) {
                if (!\array_key_exists($index, $currentValue)) {
                    $currentValue[$index] = null;
                }

                $currentValue[$index] = $this->insertFiles($keys, $currentValue[$index], $fileValue);

                return $currentValue;
            }

            throw new InvalidMap();
        }

        if (!$currentValue instanceof \stdClass) {
            throw new InvalidMap();
        }

        if (!\property_exists($currentValue, $index)) {
            $currentValue->{$index} = null;
        }

        $currentValue->{$index} = $this->insertFiles($keys, $currentValue->{$index}, $fileValue);

        return $currentValue;
    }
}
