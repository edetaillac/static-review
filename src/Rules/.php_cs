<?php

use Symfony\CS\Config\Config;
use Symfony\CS\FixerInterface;
use Symfony\CS\Finder\DefaultFinder;

$csFixers = [
    'array_element_no_space_before_comma',
    'array_element_white_space_after_comma',
    'blankline_after_open_tag',
    'duplicate_semicolon',
    'empty_return',
    'extra_empty_lines',
    'function_typehint_space',
    'hash_to_slash_comment',
    'join_function',
    'lowercase_cast',
    'method_argument_default_value',
    'namespace_no_leading_whitespace',
    'native_function_casing',
    'operators_spaces',
    'phpdoc_indent',
    'phpdoc_no_access',
    'phpdoc_no_package',
    'phpdoc_params',
    'phpdoc_scalar',
    'phpdoc_separation',
    'remove_lines_between_uses',
    'return',
    'self_accessor',
    'short_scalar_cast',
    'single_blank_line_before_namespace',
    'single_quote',
    'spaces_after_semicolon',
    'spaces_before_semicolon',
    'spaces_cast',
    'standardize_not_equal',
    'ternary_spaces',
    'unused_use',
    'align_double_arrow',
    'phpdoc_order',
    'blankline_after_open_tag',
];

return Config::create()
    ->finder(DefaultFinder::create()
        ->notPath('app/cache')
        ->notPath('app/logs')
        ->notPath('vendor')
        ->in(__DIR__)
        ->name('*.php')
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
    )
    ->fixers($csFixers)
    ->level(FixerInterface::PSR2_LEVEL)
    ->setUsingCache(true);