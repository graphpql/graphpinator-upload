# GraPHPinator Upload [![PHP](https://github.com/infinityloop-dev/graphpinator-upload/workflows/PHP/badge.svg?branch=master)](https://github.com/infinityloop-dev/graphpinator-upload/actions?query=workflow%3APHP) [![codecov](https://codecov.io/gh/infinityloop-dev/graphpinator-upload/branch/master/graph/badge.svg)](https://codecov.io/gh/infinityloop-dev/graphpinator-upload)

:zap::globe_with_meridians::zap: Module to handle multipart formdata requests.

## Introduction

This Module allows GraPHPinator to handle uploads using [multipart-formdata](https://github.com/jaydenseric/graphql-multipart-request-spec) requests.
This module hooks into Graphpinator workflow before parsing the request, reads the map and places uploaded files into according variable.

## Installation

Install package using composer

```composer require infinityloop-dev/graphpinator-upload```

## How to use

1. Implement `FileProvider`

`FileProvider` is a service which extracts files from multipart request by their key. Each HTTP framework provides it's own implementation and `FileProvider` serves as an adapter. Framework specific implementations can be found in Graphpinator packages for according framework, eg `infinityloop-dev/graphpinator-nette` contains FileProvider implementaion for Nette's HTTP abstraction.

2. Register `UploadModule` as GraPHPinator module:

```php
$uploadModule = new \Graphpinator\Upload\UploadModule($fileProvider);
$graphpinator = new \Graphpinator\Graphpinator(
    $schema,
    $catchExceptions,
    new \Graphpinator\Module\ModuleSet([$uploadModule, /* possibly other modules */]),
    $logger,
);
```

3. Register `UploadType` to your `Container`:

> This step is probably done by registering `UploadType` as service to your DI solution.

4. Optional step: Use `infinityloop-dev/graphpinator-constraint-directives` to validate uploaded files.

> For more information visit [constraint directives package](https://github.com/infinityloop-dev/graphpinator-constraint-directives).

## Known limitions

- Currently this Module can place files only to variable values and not to arguments directly.
    - This is done to ensure implementation simplicity & compatibility with other modules.
    - There is probably no benefit in placing the files directly to arguments. If you stumble upon some important edge scenario, please open an issue and we can discuss possible solution here.
