<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

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
    'braces' => false, // TODO remove as soon as inline php tags with echos are gone?
    'concat_space' => ['spacing' => 'one'],
    'echo_tag_syntax' => false,
    'global_namespace_import' => true,
    'increment_style' => ['style' => 'post'],
    'no_alternative_syntax' => false,
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
        ],
    ],
    'not_operator_with_space' => false,
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
    'yoda_style' => false,
];

$project_path = getcwd();
$finder = Finder::create()
    ->in([
        $project_path . '/app',
        $project_path . '/app_legacy',
        $project_path . '/config',
        $project_path . '/database',
        $project_path . '/lang',
        $project_path . '/public',
        $project_path . '/resources',
        $project_path . '/tests',
    ])
    ->name('*.php');

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true);
