<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/examples',
        __DIR__.'/tests'
    ]);

$config = new PhpCsFixer\Config();
return $config->setRules(['@Symfony' => true])
    ->setFinder($finder);
