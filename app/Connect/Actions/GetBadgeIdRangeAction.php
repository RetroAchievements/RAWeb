<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetBadgeIdRangeAction extends BaseApiAction
{
    public function execute(): array
    {
        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        return null;
    }

    protected function process(): array
    {
        $data = Achievement::query()
            ->select([
                DB::raw("MIN(CAST(image_name AS UNSIGNED)) AS first_badge"),
                DB::raw("MAX(CAST(image_name AS UNSIGNED)) AS last_badge"),
            ])
            ->where('image_name', '>', '00002')
            ->first();

        // 00001 is still the first selectable image, even if there aren't
        // any valid badges to select.
        $data['first_badge'] ??= 1;

        // 00000 is reserved for the placeholder image. until another badge
        // is available, make it the last available badge.
        $data['last_badge'] ??= 0;

        return [
            'Success' => true,
            'FirstBadge' => (int) $data['first_badge'],
            'NextBadge' => (int) $data['last_badge'] + 1,
        ];
    }
}
