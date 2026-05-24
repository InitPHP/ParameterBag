<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                                => true,
        '@PHP74Migration'                       => true,
        'declare_strict_types'                  => true,
        'array_syntax'                          => ['syntax' => 'short'],
        'no_unused_imports'                     => true,
        'ordered_imports'                       => [
            'sort_algorithm' => 'alpha',
            'imports_order'  => ['class', 'function', 'const'],
        ],
        'single_quote'                          => true,
        'trailing_comma_in_multiline'           => true,
        'no_trailing_whitespace'                => true,
        'no_whitespace_in_blank_line'           => true,
        'blank_line_after_opening_tag'          => true,
        'blank_line_before_statement'           => ['statements' => ['return']],
        'method_chaining_indentation'           => true,
        'native_function_invocation'            => false,
        'phpdoc_align'                          => ['align' => 'vertical'],
        'phpdoc_separation'                     => true,
        'phpdoc_trim'                           => true,
        'no_superfluous_phpdoc_tags'            => false,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
