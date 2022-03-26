<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    /*
     * RuleSet cascades/overrides:
     * @PhpCsFixer (risky)  https://raw.githubusercontent.com/FriendsOfPHP/PHP-CS-Fixer/2.18/src/RuleSet/Sets/PhpCsFixerSet.php
     *      @Symfony        https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/SymfonySet.php
     *          @PSR12      https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR12Set.php
     *              @PSR2   https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR2Set.php
     */
    '@Symfony' => true,

    // @Symfony overrides
    'blank_line_before_statement' => false,
    'concat_space' => ['spacing' => 'one'],
    'echo_tag_syntax' => false,
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
    'phpdoc_summary' => false,
    'single_line_throw' => false,
    'single_line_comment_spacing' => false,
    'single_line_comment_style' => [
        'comment_types' => ['hash'],
    ],
    'phpdoc_separation' => false,
    'phpdoc_to_comment' => false,
    'semicolon_after_instruction' => false,
    'single_quote' => false,
    'yoda_style' => false,
];

$project_path = getcwd();
$finder = Finder::create()
    ->in([
        $project_path . '/cronjobs',
        $project_path . '/lib',
        $project_path . '/public',
        $project_path . '/src',
        $project_path . '/tests',
    ])
    ->name('*.php');

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true);
