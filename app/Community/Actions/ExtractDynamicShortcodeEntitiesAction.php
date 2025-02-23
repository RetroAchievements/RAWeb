<?php

declare(strict_types=1);

namespace App\Community\Actions;

class ExtractDynamicShortcodeEntitiesAction
{
    /** @var array<string, array{pattern: string, isNumeric: bool}> */
    private array $entityConfig = [
        'achievementIds' => ['pattern' => '/\[ach=(\d+)\]/', 'isNumeric' => true],
        'gameIds' => ['pattern' => '/\[game=(\d+)\]/', 'isNumeric' => true],
        'hubIds' => ['pattern' => '/\[hub=(\d+)\]/', 'isNumeric' => true],
        'ticketIds' => ['pattern' => '/\[ticket=(\d+)\]/', 'isNumeric' => true],
        'usernames' => ['pattern' => '/\[user=([^\]]+)\]/', 'isNumeric' => false],
    ];

    /**
     * Extracts all dynamic entities from an array of text bodies.
     *
     * @param string[] $bodies array of text content containing shortcodes
     * @return array{usernames:string[], ticketIds:int[], achievementIds:int[], gameIds:int[], hubIds:int[]}
     */
    public function execute(array $bodies): array
    {
        $entities = array_fill_keys(array_keys($this->entityConfig), []);

        foreach ($bodies as $body) {
            foreach ($this->entityConfig as $entityType => $config) {
                preg_match_all($config['pattern'], $body, $matches);

                if (!empty($matches[1])) {
                    $values = $matches[1];
                    if ($config['isNumeric']) {
                        $values = array_map('intval', $values);
                    }

                    $entities[$entityType] = array_merge($entities[$entityType], $values);
                }
            }
        }

        return array_map('array_unique', $entities);
    }
}
