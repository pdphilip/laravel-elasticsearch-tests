<?php

uses(tests\TestCase::class)
    ->group('eloquent')
    ->in('EloquentTests');
uses(tests\TestCase::class)
    ->group('relationships')
    ->in('RelationshipsTests');
uses(tests\TestCase::class)
    ->group('schema')
    ->in('SchemaTests');
