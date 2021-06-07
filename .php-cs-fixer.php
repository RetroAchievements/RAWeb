<?php

// $header = <<<'EOF'
// @link https://github.com/organisation
// @copyright Organisation
// @license MIT License
// EOF;

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    /*
     * A variant of Symfony RuleSet that isn't too far from Laravel's StyleCI config
     * Use PHPStorm's Default and adapt instead of using "Laravel" / "PSR12" / "Symfony 2", those are far too greedy.
     *
     * RuleSet cascades/overrides:
     * @PhpCsFixer (risky)  https://raw.githubusercontent.com/FriendsOfPHP/PHP-CS-Fixer/2.18/src/RuleSet/Sets/PhpCsFixerSet.php
     *      @Symfony        https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/SymfonySet.php
     *          @PSR12      https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR12Set.php
     *              @PSR2   https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/src/RuleSet/Sets/PSR2Set.php
     *
     * Laravel              https://gist.github.com/laravel-shift/cab527923ed2a109dda047b97d53c200
     */
    '@Symfony' => true,

    // @Symfony overrides: Laravel
    'concat_space' => ['spacing' => 'one'],
    'increment_style' => ['style' => 'post'],
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
            'use_trait',
        ],
    ],
    'single_line_comment_style' => [
        'comment_types' => ['hash'],
    ],

    // @Symfony overrides
    'phpdoc_summary' => false,
    'phpdoc_align' => false,
    'single_line_throw' => false,
    'yoda_style' => false,

    // @Symfony overrides: V1
    'blank_line_before_statement' => false,
    'echo_tag_syntax' => false,
    'no_alternative_syntax' => false,
    'phpdoc_separation' => false,
    'phpdoc_to_comment' => false,
    'semicolon_after_instruction' => false,
    'single_quote' => false,

    // additional rules
    'not_operator_with_space' => false,
    // 'not_operator_with_successor_space' => true,
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
