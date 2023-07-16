<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// TODO use Laravel Pint as convenient wrapper for PHP CS Fixer

$rules = [
    /*
     * RuleSet cascades/overrides:
     * @PhpCsFixer (risky)  https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PhpCsFixerSet.php
     *      @Symfony        https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/SymfonySet.php
     *          @PSR12      https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR12Set.php
     *              @PSR2   https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR2Set.php
     */
    '@Symfony' => true,

    // @Symfony overrides
    'concat_space' => ['spacing' => 'one'],
    'echo_tag_syntax' => false,
    'global_namespace_import' => true,
    'increment_style' => ['style' => 'post'],
    'no_alternative_syntax' => false,
    'no_multiple_statements_per_line' => false,
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
        ],
    ],
    'not_operator_with_space' => false,
    // 'nullable_type_declaration' => false,
    'nullable_type_declaration_for_default_null_value' => true,
    'phpdoc_align' => false,
    'phpdoc_separation' => false,
    'phpdoc_summary' => false,
    'phpdoc_to_comment' => false,
    'single_line_throw' => false,
    'single_line_comment_style' => [
        'comment_types' => ['hash'],
    ],
    'semicolon_after_instruction' => false,
    'single_quote' => false,
    'statement_indentation' => false,
    'yoda_style' => false,

    // 'declare_strict_types' => true, // TODO add as soon as php files in public have been fully ported
];

$project_path = getcwd();
$finder = Finder::create()
    ->in([
        $project_path . '/app',
        $project_path . '/config',
        $project_path . '/database',
        $project_path . '/lang',
        $project_path . '/public', // no strict types here
        $project_path . '/resources', // no strict types here
        $project_path . '/tests',
    ])
    // ->filter(fn (SplFileInfo $filename) => !in_array($filename->getRealPath(), [
    //     $project_path . '/app/Helpers/util/recaptcha.php',
    // ]))
        ->name('*.php');

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true);
