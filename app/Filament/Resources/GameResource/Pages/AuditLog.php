<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Pages\ResourceAuditLog;
use App\Filament\Resources\GameResource;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

class AuditLog extends ResourceAuditLog
{
    protected static string $resource = GameResource::class;

    /**
     * @return Collection<string, mixed>
     */
    protected function createFieldLabelMap(): Collection
    {
        $fieldLabelMap = parent::createFieldLabelMap();

        $fieldLabelMap['ImageIcon'] = 'Badge';

        $fieldLabelMap['release_title'] = 'Release Title';
        $fieldLabelMap['release_region'] = 'Release Region';
        $fieldLabelMap['release_date'] = 'Release Date';
        $fieldLabelMap['release_is_canonical'] = 'Is Canonical Title';

        $fieldLabelMap['hash_name'] = 'File Name';
        $fieldLabelMap['hash_md5'] = 'MD5';
        $fieldLabelMap['hash_labels'] = 'Labels';
        $fieldLabelMap['hash_compatibility'] = 'Compatibility';
        $fieldLabelMap['hash_compatibility_tester_id'] = 'Compatibility Tester';
        $fieldLabelMap['hash_patch_url'] = 'Patch URL';
        $fieldLabelMap['hash_source'] = 'Resource Page URL';

        return $fieldLabelMap;
    }

    /**
     * @return Collection<string, Closure(int): string>
     */
    protected function createFieldValueMap(): Collection
    {
        $fieldValueMap = parent::createFieldValueMap();

        $fieldValueMap['hash_compatibility_tester_id'] = function (?int $userId): string {
            if (!$userId) {
                return '';
            }

            $user = User::find($userId);

            return $user?->display_name ?? "User ID: {$userId}";
        };

        return $fieldValueMap;
    }
}
