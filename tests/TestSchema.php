<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Tests;

use Graphpinator\SimpleContainer;
use Graphpinator\Typesystem\Argument\Argument;
use Graphpinator\Typesystem\Argument\ArgumentSet;
use Graphpinator\Typesystem\Container;
use Graphpinator\Typesystem\Field\ResolvableField;
use Graphpinator\Typesystem\Field\ResolvableFieldSet;
use Graphpinator\Typesystem\InputType;
use Graphpinator\Typesystem\Schema;
use Graphpinator\Typesystem\Type;
use Graphpinator\Upload\UploadType;
use Psr\Http\Message\UploadedFileInterface;

final class TestSchema
{
    public static ?Type $query = null;
    public static ?UploadType $upload = null;
    public static ?Type $uploadType = null;
    public static ?InputType $uploadInput = null;
    public static ?Container $container = null;

    public static function getSchema() : Schema
    {
        self::$query ??= self::getQuery();
        self::$upload ??= new UploadType();
        self::$uploadType ??= self::getUploadType();
        self::$uploadInput ??= self::getUploadInput();
        self::$container = new SimpleContainer([
            'Query' => self::$query,
            'Upload' => self::$upload,
            'UploadType' => self::$uploadType,
            'UploadInput' => self::$uploadInput,
        ], []);

        return new Schema(
            self::$container,
            self::$query,
        );
    }

    public static function getQuery() : Type
    {
        return new class extends Type
        {
            protected const NAME = 'Query';

            public function validateNonNullValue($rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    ResolvableField::create(
                        'fieldUpload',
                        TestSchema::$uploadType->notNull(),
                        static function ($parent, ?UploadedFileInterface $file) : UploadedFileInterface {
                            return $file;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'file',
                            new UploadType(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldMultiUpload',
                        TestSchema::$uploadType->notNullList(),
                        static function ($parent, array $files) : array {
                            return $files;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'files',
                            (new UploadType())->list(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldInputUpload',
                        TestSchema::$uploadType->notNull(),
                        static function ($parent, \stdClass $fileInput) : UploadedFileInterface {
                            return $fileInput->file;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'fileInput',
                            TestSchema::$uploadInput->notNull(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldInputMultiUpload',
                        TestSchema::$uploadType->notNullList(),
                        static function ($parent, \stdClass $fileInput) : array {
                            return $fileInput->files;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'fileInput',
                            TestSchema::$uploadInput->notNull(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldMultiInputUpload',
                        TestSchema::$uploadType->notNullList(),
                        static function ($parent, array $fileInputs) {
                            $return = [];

                            foreach ($fileInputs as $fileInput) {
                                $return[] = $fileInput->file;
                            }

                            return $return;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'fileInputs',
                            TestSchema::$uploadInput->notNullList(),
                        ),
                    ])),
                    ResolvableField::create(
                        'fieldMultiInputMultiUpload',
                        TestSchema::$uploadType->notNullList(),
                        static function ($parent, array $fileInputs) : array {
                            $return = [];

                            foreach ($fileInputs as $fileInput) {
                                $return += $fileInput->files;
                            }

                            return $return;
                        },
                    )->setArguments(new ArgumentSet([
                        new Argument(
                            'fileInputs',
                            TestSchema::$uploadInput->notNullList(),
                        ),
                    ])),
                ]);
            }
        };
    }

    public static function getUploadType() : Type
    {
        return new class extends Type
        {
            protected const NAME = 'UploadType';

            public function validateNonNullValue($rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : ResolvableFieldSet
            {
                return new ResolvableFieldSet([
                    new ResolvableField(
                        'fileName',
                        Container::String(),
                        static function (UploadedFileInterface $file) : ?string {
                            return $file->getClientFilename();
                        },
                    ),
                    new ResolvableField(
                        'fileContent',
                        Container::String(),
                        static function (UploadedFileInterface $file) : ?string {
                            return $file->getStream()->getContents();
                        },
                    ),
                ]);
            }
        };
    }

    public static function getUploadInput() : InputType
    {
        return new class extends InputType
        {
            protected const NAME = 'UploadInput';

            protected function getFieldDefinition() : ArgumentSet
            {
                return new ArgumentSet([
                    new Argument(
                        'file',
                        new UploadType(),
                    ),
                    new Argument(
                        'files',
                        (new UploadType())->list(),
                    ),
                ]);
            }
        };
    }
}
