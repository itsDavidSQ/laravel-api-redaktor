<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->notPath('vendor')
    ->name('*.php')
    ->ignoreVCS(true)
;
$rules = require __DIR__ . '/.php-cs-fixer.rules.php';

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ;
