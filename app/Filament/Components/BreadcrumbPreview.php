<?php

declare(strict_types=1);

namespace App\Filament\Components;

use App\Models\GameSet;
use App\Platform\Actions\BuildHubBreadcrumbsAction;
use Filament\Infolists\Components\Entry;
use Illuminate\Support\HtmlString;

class BreadcrumbPreview extends Entry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->view('filament.components.breadcrumb-preview');
    }

    public function getValue(): ?HtmlString
    {
        /** @var GameSet $gameSet */
        $gameSet = $this->getRecord();

        if (!$gameSet || !($gameSet instanceof GameSet)) {
            return null;
        }

        $breadcrumbs = (new BuildHubBreadcrumbsAction())->execute($gameSet);

        if (empty($breadcrumbs)) {
            return null;
        }

        /**
         * Check if the hub is orphaned. We consider a hub potentially orphaned if:
         *  1. The breadcrumb path has 2 (or fewer) entries. Typically this means it's just
         *     [Central] -> [My Hub] with no intermediate parents. A properly connected hub
         *     should usually have at least 3 entries like [Central] -> [Central - Type] -> [Type - Hub].
         *
         *  2. The hub's title doesn't follow the [Type - Name] pattern. For example, "Available Hub 099"
         *     fails this check, but "[Publisher - Konami]" will pass.
         *
         *  3. The hub isn't the central hub itself. Central is the root of the hierarchy and
         *     legitimately should have no parents.
         */
        $isOrphaned = count($breadcrumbs) <= 2
            && !preg_match('/^\[(.*?) - /', $gameSet->title)
            && $gameSet->id !== GameSet::centralHub()->first()?->id;

        $preview = '';
        foreach ($breadcrumbs as $index => $crumb) {
            if ($index > 0) {
                $preview .= ' <span class="text-neutral-400 px-2">→</span> ';
            }

            $preview .= sprintf(
                '<span class="inline-flex items-center gap-1.5">
                    <span class="text-sm">%s</span>
                </span>',
                e($crumb->title)
            );
        }

        if ($isOrphaned) {
            $preview .= <<<HTML
                <div class="text-sm mt-2" style="color: red;">
                    <div>Orphaned! This hub will be difficult for users to find.</div>
                    <div style="margin-top:0.5rem;">To fix this:</div>
                    <ul class="list-disc mt-1" style="margin-left: 2rem;">
                        <li>Make sure the hub has a related parent hub attached, such as [Central - Series], [Central - Developer], or [Series - Sonic the Hedgehog].</li>
                        <li>Make sure the hub title matches hub naming conventions.</li>
                    </ul>
                </div>
            HTML;
        }

        return new HtmlString($preview);
    }
}
