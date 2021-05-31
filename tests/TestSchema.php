<?php

declare(strict_types = 1);

namespace Graphpinator\Upload\Tests;

final class TestSchema
{
    use \Nette\StaticClass;

    private static array $types = [];
    private static ?\Graphpinator\Container\Container $container = null;

    public static function getSchema() : \Graphpinator\Type\Schema
    {
        return new \Graphpinator\Type\Schema(
            self::getContainer(),
            self::getQuery(),
        );
    }

    public static function getType(string $name) : object
    {
        if (\array_key_exists($name, self::$types)) {
            return self::$types[$name];
        }

        self::$types[$name] = match ($name) {
            'Query' => self::getQuery(),
            'Upload' => new \Graphpinator\Upload\UploadType(),
            'UploadType' => self::getUploadType(),
            'UploadInput' => self::getUploadInput(),
        };

        return self::$types[$name];
    }

    public static function getContainer() : \Graphpinator\Container\Container
    {
        if (self::$container !== null) {
            return self::$container;
        }

        self::$container = new \Graphpinator\Container\SimpleContainer([
            'Query' => self::getType('Query'),
            'Upload' => new \Graphpinator\Upload\UploadType(),
            'UploadType' => self::getType('UploadType'),
            'UploadInput' => self::getType('UploadInput'),
        ], []);

        return self::$container;
    }

    public static function getQuery() : \Graphpinator\Type\Type
    {
        return new class extends \Graphpinator\Type\Type
        {
            protected const NAME = 'Query';

            public function validateNonNullValue($rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Field\ResolvableFieldSet([
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldUpload',
                        TestSchema::getUploadType()->notNull(),
                        static function ($parent, ?\Psr\Http\Message\UploadedFileInterface $file) : \Psr\Http\Message\UploadedFileInterface {
                            return $file;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'file',
                            new \Graphpinator\Upload\UploadType(),
                        ),
                    ])),
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldMultiUpload',
                        TestSchema::getUploadType()->notNullList(),
                        static function ($parent, array $files) : array {
                            return $files;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'files',
                            (new \Graphpinator\Upload\UploadType())->list(),
                        ),
                    ])),
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldInputUpload',
                        TestSchema::getUploadType()->notNull(),
                        static function ($parent, \stdClass $fileInput) : \Psr\Http\Message\UploadedFileInterface {
                            return $fileInput->file;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'fileInput',
                            TestSchema::getUploadInput()->notNull(),
                        ),
                    ])),
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldInputMultiUpload',
                        TestSchema::getUploadType()->notNullList(),
                        static function ($parent, \stdClass $fileInput) : array {
                            return $fileInput->files;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'fileInput',
                            TestSchema::getUploadInput()->notNull(),
                        ),
                    ])),
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldMultiInputUpload',
                        TestSchema::getUploadType()->notNullList(),
                        static function ($parent, array $fileInputs) {
                            $return = [];

                            foreach ($fileInputs as $fileInput) {
                                $return[] = $fileInput->file;
                            }

                            return $return;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'fileInputs',
                            TestSchema::getUploadInput()->notNullList(),
                        ),
                    ])),
                    \Graphpinator\Field\ResolvableField::create(
                        'fieldMultiInputMultiUpload',
                        TestSchema::getUploadType()->notNullList(),
                        static function ($parent, array $fileInputs) {
                            $return = [];

                            foreach ($fileInputs as $fileInput) {
                                $return += $fileInput->files;
                            }

                            return $return;
                        },
                    )->setArguments(new \Graphpinator\Argument\ArgumentSet([
                        new \Graphpinator\Argument\Argument(
                            'fileInputs',
                            TestSchema::getUploadInput()->notNullList(),
                        ),
                    ])),
                ]);
            }
        };
    }


    public static function getUploadType() : \Graphpinator\Type\Type
    {
        return new class extends \Graphpinator\Type\Type
        {
            protected const NAME = 'UploadType';

            public function validateNonNullValue($rawValue) : bool
            {
                return true;
            }

            protected function getFieldDefinition() : \Graphpinator\Field\ResolvableFieldSet
            {
                return new \Graphpinator\Field\ResolvableFieldSet([
                    new \Graphpinator\Field\ResolvableField(
                        'fileName',
                        \Graphpinator\Container\Container::String(),
                        static function (\Psr\Http\Message\UploadedFileInterface $file) : string {
                            return $file->getClientFilename();
                        },
                    ),
                    new \Graphpinator\Field\ResolvableField(
                        'fileContent',
                        \Graphpinator\Container\Container::String(),
                        static function (\Psr\Http\Message\UploadedFileInterface $file) : string {
                            return $file->getStream()->getContents();
                        },
                    ),
                ]);
            }
        };
    }

    public static function getUploadInput() : \Graphpinator\Type\InputType
    {
        return new class extends \Graphpinator\Type\InputType
        {
            protected const NAME = 'UploadInput';

            protected function getFieldDefinition() : \Graphpinator\Argument\ArgumentSet
            {
                return new \Graphpinator\Argument\ArgumentSet([
                    new \Graphpinator\Argument\Argument(
                        'file',
                        new \Graphpinator\Upload\UploadType(),
                    ),
                    new \Graphpinator\Argument\Argument(
                        'files',
                        (new \Graphpinator\Upload\UploadType())->list(),
                    ),
                ]);
            }
        };
    }
}
