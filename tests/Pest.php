<?php

uses(Tests\TestCase::class)
    ->group('eloquent')
    ->in('EloquentTests');
uses(Tests\TestCase::class)
    ->group('relationships')
    ->in('RelationshipsTests');
uses(Tests\TestCase::class)
    ->group('schema')
    ->in('SchemaTests');
