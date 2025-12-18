<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Tests;

use Graphpinator\Graphpinator;
use Graphpinator\Module\ModuleSet;
use Graphpinator\Normalizer\Exception\VariableTypeMismatch;
use Graphpinator\Request\JsonRequestFactory;
use Graphpinator\Upload\Exception\ConflictingMap;
use Graphpinator\Upload\Exception\InvalidMap;
use Graphpinator\Upload\Exception\OnlyVariablesSupported;
use Graphpinator\Upload\Exception\UninitializedVariable;
use Graphpinator\Upload\FileProvider;
use Graphpinator\Upload\UploadModule;
use Graphpinator\Value\Exception\InvalidValue;
use Infinityloop\Utils\Json;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

final class UploadModuleTest extends TestCase
{
    public static function simpleDataProvider() : array
    {
        return [
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName fileContent } }',
                    'variables' => (object) ['var1' => null],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldUpload' => ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload!) { fieldUpload(file: $var1) { fileName fileContent } }',
                    'variables' => (object) ['var1' => null],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldUpload' => ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1.0", "variables.var1.1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: [Upload]) { fieldMultiUpload(files: $var1) { fileName fileContent } }',
                    'variables' => (object) ['var1' => null],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldMultiUpload' => [
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                        ],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1.file"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: UploadInput! = {}) { 
                        fieldInputUpload(fileInput: $var1) { fileName fileContent } 
                    }',
                    'variables' => (object) ['var1' => (object) []],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldInputUpload' => ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1.files.0", "variables.var1.files.1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: UploadInput!) { 
                        fieldInputMultiUpload(fileInput: $var1) { fileName fileContent } 
                    }',
                    'variables' => (object) ['var1' => (object) ['files' => null]],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldInputMultiUpload' => [
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                        ],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1.0.file"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: [UploadInput!]!) { 
                        fieldMultiInputUpload(fileInputs: $var1) { fileName fileContent } 
                    }',
                    'variables' => (object) ['var1' => [(object) ['file' => null]]],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldMultiInputUpload' => [
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                        ],
                    ],
                ]),
            ],
            [
                '{ "0": ["variables.var1.0.files.0", "variables.var1.0.files.1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: [UploadInput!]!) { 
                        fieldMultiInputMultiUpload(fileInputs: $var1) { fileName fileContent } 
                    }',
                    'variables' => (object) ['var1' => [(object) ['files' => null]]],
                ]),
                Json::fromNative((object) [
                    'data' => [
                        'fieldMultiInputMultiUpload' => [
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                            ['fileName' => 'a.txt', 'fileContent' => 'test file'],
                        ],
                    ],
                ]),
            ],
        ];
    }

    public static function invalidDataProvider() : array
    {
        return [
            [
                '{ "0": ["queryName.fileUpload.file"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                ]),
                OnlyVariablesSupported::class,
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                ]),
                UninitializedVariable::class,
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                    'variables' => (object) ['var1' => 123],
                ]),
                ConflictingMap::class,
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                    'variables' => (object) ['var1' => []],
                ]),
                InvalidMap::class,
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                    'variables' => (object) ['var1' => (object) []],
                ]),
                InvalidMap::class,
            ],
            [
                '{ "0": ["variables.var1.invalid"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Upload) { fieldUpload(file: $var1) { fileName } }',
                    'variables' => (object) ['var1' => (object) []],
                ]),
                InvalidValue::class,
                'Invalid value resolved for type "Upload" - got object.',
            ],
            [
                '{ "0": ["variables.var1.invalid", "variables.var1.1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: [Upload]) { fieldMultiUpload(files: $var1) { fileName } }',
                    'variables' => (object) ['var1' => []],
                ]),
                InvalidMap::class,
            ],
            [
                '{ "0": ["variables.var1.0", "variables.var1.1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: [Upload]) { fieldMultiUpload(files: $var1) { fileName } }',
                    'variables' => (object) ['var1' => (object) []],
                ]),
                InvalidMap::class,
            ],
            [
                '{ "0": ["variables.var1.0"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: UploadInput! = {}) { fieldInputUpload(fileInput: $var1) { fileName } }',
                    'variables' => (object) ['var1' => []],
                ]),
                InvalidValue::class,
                'Invalid value resolved for type "UploadInput" - got list.',
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: UploadInput) { fieldInputUpload(fileInput: $var1) { fileName } }',
                    'variables' => (object) ['var1' => null],
                ]),
                VariableTypeMismatch::class,
            ],
            [
                '{ "0": ["variables.var1"] }',
                Json::fromNative((object) [
                    'query' => 'query queryName($var1: Int) { fieldInputUpload(fileInput: $var1) { fileName } }',
                    'variables' => (object) ['var1' => null],
                ]),
                VariableTypeMismatch::class,
            ],
        ];
    }

    /**
     * @dataProvider simpleDataProvider
     * @param string $map
     * @param Json $request
     * @param Json $expected
     */
    public function testSimple(string $map, Json $request, Json $expected) : void
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn('test file');
        $file = $this->createStub(UploadedFileInterface::class);
        $file->method('getClientFilename')->willReturn('a.txt');
        $file->method('getStream')->willReturn($stream);
        $fileProvider = $this->createStub(FileProvider::class);
        $fileProvider->method('getMap')->willReturn(Json::fromString($map));
        $fileProvider->method('getFile')->willReturn($file);
        $graphpinator = new Graphpinator(TestSchema::getSchema(), false, new ModuleSet([
            new UploadModule($fileProvider),
        ]));
        $result = $graphpinator->run(new JsonRequestFactory($request));

        self::assertSame($expected->toString(), $result->toString());
    }

    /**
     * @dataProvider invalidDataProvider
     * @param string $map
     * @param Json $request
     * @param string $exception
     */
    public function testInvalid(string $map, Json $request, string $exception, ?string $message = null) : void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message
            ?? \constant($exception . '::MESSAGE'));

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn('test file');
        $file = $this->createStub(UploadedFileInterface::class);
        $file->method('getClientFilename')->willReturn('a.txt');
        $file->method('getStream')->willReturn($stream);
        $fileProvider = $this->createStub(FileProvider::class);
        $fileProvider->method('getMap')->willReturn(Json::fromString($map));
        $fileProvider->method('getFile')->willReturn($file);
        $graphpinator = new Graphpinator(TestSchema::getSchema(), false, new ModuleSet([
            new UploadModule($fileProvider),
        ]));
        $graphpinator->run(new JsonRequestFactory($request));
    }
}
