<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests/functional')
    ->in(__DIR__ . '/tests/unit')
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // override some Symfony rules
        'blank_line_before_return' => false,
        'cast_spaces' => false,
        'concat_space' => array('spacing' => 'one'),
        'is_null' => array('use_yoda_style' => false),
        'no_singleline_whitespace_before_semicolons' => false,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'yoda_style' => false,
        'blank_line_before_statement' => null,

        // additional rules
        'array_syntax' => array('syntax' => 'short'),
        'general_phpdoc_annotation_remove' => array('annotations' => array('@author', '@inheritdoc')),
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_return' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'strict_comparison' => true,
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
