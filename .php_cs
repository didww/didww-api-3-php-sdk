<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/examples',
        __DIR__.'/tests'
    ]);

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
    ])->setFinder($finder);

