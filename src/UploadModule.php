<?php

declare(strict_types = 1);

namespace Graphpinator\Upload;

final class UploadModule implements \Graphpinator\Module\Module
{
    public function __construct(
        private FileProvider $fileProvider,
    )
    {
    }

    public function processRequest(\Graphpinator\Request\Request $request) : \Graphpinator\Request\Request
    {
        $variables = $request->getVariables();
        $map = $this->fileProvider->getMap()
            ?? \Infinityloop\Utils\Json::fromNative([]);

        foreach ($map as $fileKey => $locations) {
            $fileValue = $this->fileProvider->getFile((string) $fileKey);

            foreach ($locations as $location) {
                /**
                 * Array reverse is done so we can use array_pop (O(1)) instead of array_shift (O(n))
                 */
                $keys = \array_reverse(\explode('.', $location));

                if (\array_pop($keys) !== 'variables') {
                    throw new \Graphpinator\Upload\Exception\OnlyVariablesSupported();
                }

                $variableName = \array_pop($keys);

                if (!\property_exists($variables, $variableName)) {
                    throw new \Graphpinator\Upload\Exception\UninitializedVariable();
                }

                $variableValue = $variables->{$variableName};
                $variables->{$variableName} = $this->insertFiles($keys, $variableValue, $fileValue);
            }
        }

        return $request;
    }

    public function processParsed(\Graphpinator\Parser\ParsedRequest $request) : \Graphpinator\Parser\ParsedRequest
    {
        return $request;
    }

    public function processNormalized(\Graphpinator\Normalizer\NormalizedRequest $request) : \Graphpinator\Normalizer\NormalizedRequest
    {
        return $request;
    }

    public function processFinalized(\Graphpinator\Normalizer\FinalizedRequest $request) : \Graphpinator\Normalizer\FinalizedRequest
    {
        return $request;
    }

    public function processResult(\Graphpinator\Result $result) : \Graphpinator\Result
    {
        return $result;
    }

    private function insertFiles(
        array &$keys,
        string|int|float|bool|array|\stdClass|null $currentValue,
        \Psr\Http\Message\UploadedFileInterface $fileValue,
    ) : array|\stdClass|\Psr\Http\Message\UploadedFileInterface
    {
        if (\is_scalar($currentValue)) {
            throw new \Graphpinator\Upload\Exception\ConflictingMap();
        }

        if (\count($keys) === 0) {
            if ($currentValue === null) {
                return $fileValue;
            }

            throw new \Graphpinator\Upload\Exception\InvalidMap();
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

            throw new \Graphpinator\Upload\Exception\InvalidMap();
        }

        if (!$currentValue instanceof \stdClass) {
            throw new \Graphpinator\Upload\Exception\InvalidMap();
        }

        if (!\property_exists($currentValue, $index)) {
            $currentValue->{$index} = null;
        }

        $currentValue->{$index} = $this->insertFiles($keys, $currentValue->{$index}, $fileValue);

        return $currentValue;
    }
}
