<?php

uses(Tests\TestCase::class)
    ->group('eloquent')
    ->in('Eloquent');
uses(Tests\TestCase::class)
    ->group('relationships')
    ->in('Relationships');
uses(Tests\TestCase::class)
    ->group('schema')
    ->in('Schema');
