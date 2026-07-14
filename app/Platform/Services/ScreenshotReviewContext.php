<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\System;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotReviewDecision;
use App\Platform\Enums\ScreenshotType;
use Closure;
use Illuminate\Support\Collection;

final class ScreenshotReviewContext
{
    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(
        private GameScreenshot $screenshot,
        private ScreenshotResolutionService $resolutionService,
    ) {
    }

    public static function make(GameScreenshot $screenshot): self
    {
        return new self($screenshot, app(ScreenshotResolutionService::class));
    }

    public function screenshot(): GameScreenshot
    {
        return $this->screenshot;
    }

    public function game(): ?Game
    {
        return $this->remember('game', function (): ?Game {
            $this->screenshot->loadMissing([
                'game.system',
                'game.gameScreenshots',
            ]);

            return $this->screenshot->game;
        });
    }

    public function system(): ?System
    {
        return $this->game()?->system;
    }

    public function isPixelated(): bool
    {
        return !($this->system()?->supports_upscaled_screenshots ?? true);
    }

    public function imageRenderingFor(?GameScreenshot $screenshot): ?string
    {
        if (!$screenshot) {
            return null;
        }

        if ($this->isPixelated()) {
            return 'pixelated';
        }

        if (!$screenshot->width || $screenshot->width <= 0) {
            return null;
        }

        if ($screenshot->width <= 640) {
            return 'crisp-edges';
        }

        return null;
    }

    /**
     * once() and Cache::* aren't really great fits for what we want here.
     * We need keyed, per-instance memoization.
     *
     * @template T
     * @param Closure(): T $resolve
     * @return T
     */
    private function remember(string $key, Closure $resolve): mixed
    {
        if (!array_key_exists($key, $this->memo)) {
            $this->memo[$key] = $resolve();
        }

        return $this->memo[$key];
    }

    public function formatResolution(?GameScreenshot $screenshot = null): ?string
    {
        $screenshot ??= $this->screenshot;

        return ($screenshot->width && $screenshot->height) ? "{$screenshot->width}x{$screenshot->height}" : null;
    }

    public function hasInvalidResolution(?GameScreenshot $screenshot = null): bool
    {
        $screenshot ??= $this->screenshot;

        if (!$screenshot->width || !$screenshot->height) {
            return false;
        }

        $system = $this->system();
        if (!$system || empty($system->screenshot_resolutions)) {
            return false;
        }

        return !$this->resolutionService->isValidResolution($screenshot->width, $screenshot->height, $system);
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function gameScreenshots(): Collection
    {
        return $this->remember('gameScreenshots', fn (): Collection => $this->game()?->gameScreenshots ?? collect());
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function approvedPrimaries(): Collection
    {
        return $this->remember('approvedPrimaries', fn (): Collection => $this->gameScreenshots()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->status === GameScreenshotStatus::Approved
                && $screenshot->is_primary
                && $screenshot->width !== null
                && $screenshot->height !== null
            )
            ->values()
        );
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function approvedScreenshotsForType(ScreenshotType $type): Collection
    {
        return $this->remember("approvedScreenshotsForType:{$type->value}", fn (): Collection => $this->gameScreenshots()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->status === GameScreenshotStatus::Approved
                && $screenshot->type === $type
            )
            ->values()
        );
    }

    public function currentPrimaryForType(ScreenshotType $type): ?GameScreenshot
    {
        return $this->remember("currentPrimaryForType:{$type->value}", function () use ($type): ?GameScreenshot {
            /** @var GameScreenshot|null $primary */
            $primary = $this->approvedScreenshotsForType($type)
                ->first(fn (GameScreenshot $screenshot): bool => $screenshot->is_primary);

            return $primary;
        });
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function matchingPendingCompanions(): Collection
    {
        return $this->remember('matchingPendingCompanions', function (): Collection {
            if (!$this->screenshot->width || !$this->screenshot->height || !$this->screenshot->captured_by_user_id) {
                return collect();
            }

            return $this->gameScreenshots()
                ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->status === GameScreenshotStatus::Pending
                    && $screenshot->id !== $this->screenshot->id
                    && $screenshot->captured_by_user_id === $this->screenshot->captured_by_user_id
                    && $screenshot->type !== $this->screenshot->type
                    && $screenshot->width === $this->screenshot->width
                    && $screenshot->height === $this->screenshot->height
                )
                ->values();
        });
    }

    /**
     * @return Collection<int, ScreenshotType>
     */
    public function matchingPendingCompanionTypes(): Collection
    {
        return $this->remember('matchingPendingCompanionTypes', fn (): Collection => $this->matchingPendingCompanions()
            ->map(fn (GameScreenshot $screenshot): ScreenshotType => $screenshot->type)
            ->unique(fn (ScreenshotType $type): string => $type->value)
            ->values()
        );
    }

    public function hasUnresolvedPrimaryPreviewMismatch(): bool
    {
        return $this->remember('hasUnresolvedPrimaryPreviewMismatch', function (): bool {
            $matchingPendingTypes = $this->matchingPendingCompanionTypesForMismatches();

            return $this->mismatchedPrimaryTypes()->contains(
                fn (ScreenshotType $mismatchedType): bool => !$matchingPendingTypes->contains(
                    fn (ScreenshotType $pendingType): bool => $pendingType === $mismatchedType,
                ),
            );
        });
    }

    /**
     * @return Collection<int, ScreenshotType>
     */
    public function mismatchedPrimaryTypes(): Collection
    {
        return $this->remember('mismatchedPrimaryTypes', function (): Collection {
            if (!$this->screenshot->width || !$this->screenshot->height) {
                return collect();
            }

            return $this->approvedPrimaries()
                ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->type !== $this->screenshot->type)
                ->filter(fn (GameScreenshot $screenshot): bool => $this->hasInvalidResolution($screenshot)
                    || $screenshot->width !== $this->screenshot->width
                    || $screenshot->height !== $this->screenshot->height
                )
                ->sortBy(fn (GameScreenshot $screenshot): int => $screenshot->type->sortOrder())
                ->map(fn (GameScreenshot $screenshot): ScreenshotType => $screenshot->type)
                ->unique(fn (ScreenshotType $type): string => $type->value)
                ->values();
        });
    }

    public function suggestedRejectionReason(): ?GameScreenshotRejectionReason
    {
        return $this->remember('suggestedRejectionReason', function (): ?GameScreenshotRejectionReason {
            if ($this->hasInvalidResolution()) {
                return GameScreenshotRejectionReason::WrongResolution;
            }

            if ($this->hasUnresolvedPrimaryPreviewMismatch()) {
                return GameScreenshotRejectionReason::MissingMatchingCompanion;
            }

            return null;
        });
    }

    public function recommendedAction(): ?ScreenshotReviewDecision
    {
        return $this->remember('recommendedAction', function (): ?ScreenshotReviewDecision {
            if (!$this->formatResolution()) {
                return null;
            }

            if ($this->hasUnresolvedPrimaryPreviewMismatch()) {
                return ScreenshotReviewDecision::Reject;
            }

            if ($this->screenshot->type !== ScreenshotType::Ingame) {
                return ScreenshotReviewDecision::Primary;
            }

            if ($this->willPromoteIngameToPrimary() || $this->canApproveAndReplaceIngamePrimary()) {
                return ScreenshotReviewDecision::Primary;
            }

            return null;
        });
    }

    /**
     * @return array<int, array{modalLabel: string, badgeLabel: string, tone: 'danger'|'warning'|'success'}>
     */
    public function candidateImageCues(): array
    {
        return $this->remember('candidateImageCues', function (): array {
            $missingCompanionCue = $this->missingPendingCompanionCueLabels();
            $primaryMatchCue = $this->submissionPrimaryMatchCueLabels();
            $remainingPrimaryIssueCue = $this->remainingPrimaryIssueCueLabels();
            $galleryMismatchCue = $this->shouldShowGalleryMismatchAsCandidateCue()
                ? $this->submissionGalleryMismatchCueLabels()
                : null;

            return collect([
                $this->canFixCurrentPrimary() ? [
                    'modalLabel' => 'Can replace invalid primary',
                    'badgeLabel' => 'Fixes invalid primary',
                    'tone' => 'success',
                ] : null,
                $missingCompanionCue ? [...$missingCompanionCue, 'tone' => 'danger'] : null,
                $primaryMatchCue ? [...$primaryMatchCue, 'tone' => 'success'] : null,
                $remainingPrimaryIssueCue !== null && $missingCompanionCue === null
                    ? [...$remainingPrimaryIssueCue, 'tone' => 'warning']
                    : null,
                $galleryMismatchCue ? [...$galleryMismatchCue, 'tone' => 'warning'] : null,
            ])->filter()->values()->all();
        });
    }

    public function primaryDecisionHelp(): string
    {
        $typeLabel = strtolower($this->screenshot->type->label());

        return $this->currentPrimaryForType($this->screenshot->type)
            ? "Approve and replace the current {$typeLabel} primary."
            : "Approve as the first {$typeLabel} primary.";
    }

    public function primaryDecisionDetail(): ?string
    {
        if (!$this->canFixCurrentPrimary()) {
            return null;
        }

        $existingPrimary = $this->currentPrimaryForType($this->screenshot->type);
        if (!$existingPrimary) {
            return null;
        }

        $currentResolution = $this->formatResolution($existingPrimary);
        $typeLabel = strtolower($this->screenshot->type->label());

        return "Replaces the current {$currentResolution} {$typeLabel} primary, which has an invalid size.";
    }

    public function galleryDecisionDetail(): ?string
    {
        $warningData = $this->gallerySizeWarningData();
        if (!$warningData) {
            return null;
        }

        return "After approval the gallery will contain {$warningData['count']} in-game sizes{$warningData['summary']}.";
    }

    /**
     * @return array{modalLabel: string, badgeLabel: string}|null
     */
    public function currentPrimaryMismatchCueLabels(GameScreenshot $existingPrimary): ?array
    {
        if (!$this->screenshot->width || !$this->screenshot->height || !$existingPrimary->width || !$existingPrimary->height) {
            return null;
        }

        $submissionResolution = $this->formatResolution();
        $currentResolution = $this->formatResolution($existingPrimary);

        if ($submissionResolution === $currentResolution) {
            return null;
        }

        $matchingPrimaryTypes = $this->approvedPrimaries()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->type !== $existingPrimary->type)
            ->filter(fn (GameScreenshot $screenshot): bool => $this->formatResolution($screenshot) === $submissionResolution)
            ->sortBy(fn (GameScreenshot $screenshot): int => $screenshot->type->sortOrder())
            ->map(fn (GameScreenshot $screenshot): ScreenshotType => $screenshot->type)
            ->values();

        if ($matchingPrimaryTypes->isEmpty()) {
            return null;
        }

        $typeList = $this->formatTypeList($matchingPrimaryTypes);

        return [
            'modalLabel' => 'Differs from ' . $typeList . ' primary size',
            'badgeLabel' => 'Differs from ' . $this->formatTypeList($matchingPrimaryTypes, short: true),
        ];
    }

    public function approvedIngameCount(): int
    {
        return $this->approvedScreenshotsForType(ScreenshotType::Ingame)->count();
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function otherPendingForGame(): Collection
    {
        return $this->remember('otherPendingForGame', fn (): Collection => $this->gameScreenshots()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->status === GameScreenshotStatus::Pending
                && $screenshot->id !== $this->screenshot->id
            )
            ->sortBy([
                fn (GameScreenshot $a, GameScreenshot $b): int => $a->type->sortOrder() <=> $b->type->sortOrder(),
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
        );
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function allPendingForGame(): Collection
    {
        return $this->remember('allPendingForGame', fn (): Collection => $this->gameScreenshots()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->status === GameScreenshotStatus::Pending)
            ->sortBy([
                fn (GameScreenshot $a, GameScreenshot $b): int => $a->type->sortOrder() <=> $b->type->sortOrder(),
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
        );
    }

    /**
     * @return array{items: array<int, array{recordKey: string, typeLabel: string, resolution: string, submitterLabel: string, url: string|null, isCurrent: bool}>}|null
     */
    public function allPendingForGameContextData(): ?array
    {
        if ($this->otherPendingForGame()->isEmpty()) {
            return null;
        }

        $submissions = $this->allPendingForGame();

        return [
            'items' => $submissions
                ->map(fn (GameScreenshot $submission): array => [
                    'recordKey' => (string) $submission->getKey(),
                    'typeLabel' => $submission->type->label(),
                    'resolution' => $this->formatResolution($submission) ?? '?',
                    'submitterLabel' => $submission->capturedBy?->display_name ?? 'Unknown user',
                    'url' => $submission->media?->getUrl(),
                    'isCurrent' => $submission->id === $this->screenshot->id,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, GameScreenshot>
     */
    public function approvedGalleryScreenshots(): Collection
    {
        return $this->remember('approvedGalleryScreenshots', fn (): Collection => $this->approvedScreenshotsForType(ScreenshotType::Ingame)
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->width !== null && $screenshot->height !== null)
            ->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
        );
    }

    /**
     * @return array<int, array{url: string|null, resolution: string, label: string, typeLabel: string, submitterLabel: string, imageRendering: string|null}>
     */
    public function approvedGalleryItemsViewData(): array
    {
        return $this->approvedGalleryScreenshots()
            ->map(function (GameScreenshot $screenshot): array {
                $screenshot->loadMissing(['capturedBy', 'media']);

                $resolution = $this->formatResolution($screenshot) ?? '?';

                return [
                    'url' => $screenshot->media?->getUrl(),
                    'resolution' => $resolution,
                    'label' => "Gallery image ({$resolution})",
                    'typeLabel' => $screenshot->type->label(),
                    'submitterLabel' => $screenshot->capturedBy?->display_name ?? 'Unknown user',
                    'imageRendering' => $this->imageRenderingFor($screenshot),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{typeLabel: string, resolution: string, url: string|null, invalid: bool}>
     */
    public function currentPrimaryContextItems(): array
    {
        return collect([
            ScreenshotType::Title,
            ScreenshotType::Ingame,
            ScreenshotType::Completion,
        ])
            ->map(function (ScreenshotType $type): ?array {
                $primary = $this->currentPrimaryForType($type);
                if (!$primary) {
                    return null;
                }

                $primary->loadMissing('media');

                return [
                    'typeLabel' => $type->label(),
                    'resolution' => $this->formatResolution($primary) ?? '?',
                    'url' => $primary->media?->getUrl(),
                    'invalid' => $this->hasInvalidResolution($primary),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function canFixCurrentPrimary(): bool
    {
        if (!$this->screenshot->width || !$this->screenshot->height) {
            return false;
        }

        $existingPrimary = $this->currentPrimaryForType($this->screenshot->type);
        if (!$existingPrimary) {
            return false;
        }

        return
            $this->hasInvalidResolution($existingPrimary)
            && !$this->hasInvalidResolution($this->screenshot);
    }

    public function willPromoteIngameToPrimary(): bool
    {
        if ($this->screenshot->type !== ScreenshotType::Ingame) {
            return false;
        }

        $existingPrimary = $this->currentPrimaryForType(ScreenshotType::Ingame);
        if (!$existingPrimary) {
            return true;
        }

        return
            $this->hasInvalidResolution($existingPrimary)
            && !$this->hasInvalidResolution($this->screenshot);
    }

    public function canApproveAndReplaceIngamePrimary(): bool
    {
        if ($this->screenshot->type !== ScreenshotType::Ingame || $this->hasInvalidResolution()) {
            return false;
        }

        if ($this->willPromoteIngameToPrimary()) {
            return false;
        }

        $existingPrimary = $this->currentPrimaryForType(ScreenshotType::Ingame);
        if (!$existingPrimary || !$existingPrimary->width || !$existingPrimary->height) {
            return false;
        }

        return
            $existingPrimary->width !== $this->screenshot->width
            || $existingPrimary->height !== $this->screenshot->height;
    }

    public function canApproveToGallery(): bool
    {
        if ($this->screenshot->type !== ScreenshotType::Ingame) {
            return false;
        }

        if (!$this->currentPrimaryForType(ScreenshotType::Ingame)) {
            return false;
        }

        if ($this->willPromoteIngameToPrimary()) {
            return false;
        }

        return $this->approvedIngameCount() < ScreenshotType::Ingame->approvedCap();
    }

    /**
     * Whether the reviewer can promote this submission to primary while keeping the
     * current primary as a non-primary gallery image rather than retiring it. Only
     * in-game screenshots have a gallery to fall back into, and keeping the old primary
     * consumes one more slot, so the game must be under the in-game cap.
     */
    public function canKeepReplacedPrimaryInGallery(): bool
    {
        if ($this->screenshot->type !== ScreenshotType::Ingame) {
            return false;
        }

        if (!$this->currentPrimaryForType(ScreenshotType::Ingame)) {
            return false;
        }

        return $this->approvedIngameCount() < ScreenshotType::Ingame->approvedCap();
    }

    /**
     * Approved screenshots on the same game whose dimensions don't match this one.
     *
     * Only approved screenshots whose own resolution is valid for the system are
     * counted. A legacy approved screenshot at an invalid resolution is itself the
     * thing that needs to be fixed rather than a baseline the submission should match.
     *
     * @return array<string, array{count: int, types: array<string, int>}>
     */
    public function approvedResolutionMismatches(): array
    {
        return $this->remember('approvedResolutionMismatches', function (): array {
            if (!$this->screenshot->width || !$this->screenshot->height) {
                return [];
            }

            $game = $this->game();
            if (!$game) {
                return [];
            }

            $system = $game->system;
            if (empty($system?->screenshot_resolutions)) {
                return [];
            }

            $mismatches = [];

            foreach ($game->gameScreenshots as $other) {
                if ($other->id === $this->screenshot->id) {
                    continue;
                }
                if ($other->status !== GameScreenshotStatus::Approved) {
                    continue;
                }
                if (!$other->width || !$other->height) {
                    continue;
                }
                if ($other->width === $this->screenshot->width && $other->height === $this->screenshot->height) {
                    continue;
                }
                if (!$this->resolutionService->isValidResolution($other->width, $other->height, $system)) {
                    continue;
                }

                $key = "{$other->width}x{$other->height}";
                $typeLabel = $other->type->label();

                if (!isset($mismatches[$key])) {
                    $mismatches[$key] = ['count' => 0, 'types' => []];
                }

                $mismatches[$key]['count']++;
                $mismatches[$key]['types'][$typeLabel] = ($mismatches[$key]['types'][$typeLabel] ?? 0) + 1;
            }

            return $mismatches;
        });
    }

    /**
     * @return Collection<int, ScreenshotType>
     */
    public function alignedPrimaryTypes(): Collection
    {
        $targetResolution = $this->formatResolution();
        if (!$targetResolution) {
            return collect();
        }

        return $this->approvedPrimaries()
            ->filter(fn (GameScreenshot $screenshot): bool => $this->formatResolution($screenshot) === $targetResolution
                && !$this->hasInvalidResolution($screenshot)
            )
            ->sortBy(fn (GameScreenshot $screenshot): int => $screenshot->type->sortOrder())
            ->map(fn (GameScreenshot $screenshot): ScreenshotType => $screenshot->type)
            ->unique(fn (ScreenshotType $type): string => $type->value)
            ->values();
    }

    /**
     * @return array{count: int, summary: string}|null
     */
    public function gallerySizeWarningData(): ?array
    {
        return $this->remember('gallerySizeWarningData', function (): ?array {
            if (!$this->screenshot->width || !$this->screenshot->height || $this->screenshot->type !== ScreenshotType::Ingame) {
                return null;
            }

            $candidateResolution = $this->formatResolution();

            $galleryResolutions = $this->gameScreenshots()
                ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->type === ScreenshotType::Ingame
                    && $screenshot->status === GameScreenshotStatus::Approved
                    && $screenshot->width !== null
                    && $screenshot->height !== null
                )
                ->map(fn (GameScreenshot $screenshot): string => $this->formatResolution($screenshot))
                ->push($candidateResolution)
                ->unique()
                ->values();

            if ($galleryResolutions->count() <= 1) {
                return null;
            }

            $resolutionSummary = $galleryResolutions->count() <= 3
                ? ': ' . $galleryResolutions->implode(', ')
                : '';

            return [
                'count' => $galleryResolutions->count(),
                'summary' => $resolutionSummary,
            ];
        });
    }

    /**
     * @return Collection<int, ScreenshotType>
     */
    private function matchingPendingCompanionTypesForMismatches(): Collection
    {
        return $this->matchingPendingCompanionTypes()
            ->filter(fn (ScreenshotType $type): bool => $this->mismatchedPrimaryTypes()->contains(
                fn (ScreenshotType $mismatchedType): bool => $mismatchedType === $type,
            ))
            ->values();
    }

    /**
     * @return array{modalLabel: string, badgeLabel: string}|null
     */
    private function remainingPrimaryIssueCueLabels(): ?array
    {
        $mismatchedTypes = $this->mismatchedPrimaryTypes();
        if ($mismatchedTypes->isEmpty()) {
            return null;
        }

        $matchingPendingTypes = $this->matchingPendingCompanionTypesForMismatches();
        if ($matchingPendingTypes->isNotEmpty()) {
            $typeList = $this->formatTypeList($matchingPendingTypes);

            return [
                'modalLabel' => "Approve matching pending {$typeList} too to keep public previews aligned",
                'badgeLabel' => 'Needs matching ' . $this->formatTypeList($matchingPendingTypes, short: true),
            ];
        }

        $typeList = $this->formatTypeList($mismatchedTypes);
        $primaryLabel = $mismatchedTypes->count() === 1 ? 'primary is' : 'primary screenshots are';

        return [
            'modalLabel' => "Primary screenshots use different resolutions until {$typeList} {$primaryLabel} replaced",
            'badgeLabel' => 'Different primary sizes',
        ];
    }

    /**
     * @return array{modalLabel: string, badgeLabel: string}|null
     */
    private function missingPendingCompanionCueLabels(): ?array
    {
        if (!$this->hasUnresolvedPrimaryPreviewMismatch()) {
            return null;
        }

        $mismatchedTypes = $this->mismatchedPrimaryTypes();
        $typeList = $this->formatTypeList($mismatchedTypes);

        return [
            'modalLabel' => "No pending {$typeList} matches this size",
            'badgeLabel' => 'No matching ' . $this->formatTypeList($mismatchedTypes, short: true),
        ];
    }

    /**
     * @return array{modalLabel: string, badgeLabel: string}|null
     */
    private function submissionPrimaryMatchCueLabels(): ?array
    {
        if (!$this->screenshot->width || !$this->screenshot->height) {
            return null;
        }

        $submissionResolution = $this->formatResolution();

        $matchingPrimaryTypes = $this->approvedPrimaries()
            ->filter(fn (GameScreenshot $screenshot): bool => $screenshot->type !== $this->screenshot->type)
            ->filter(fn (GameScreenshot $screenshot): bool => $this->formatResolution($screenshot) === $submissionResolution)
            ->sortBy(fn (GameScreenshot $screenshot): int => $screenshot->type->sortOrder())
            ->map(fn (GameScreenshot $screenshot): ScreenshotType => $screenshot->type)
            ->values();

        if ($matchingPrimaryTypes->isEmpty()) {
            return null;
        }

        $typeList = $this->formatTypeList($matchingPrimaryTypes);

        return [
            'modalLabel' => "Matches {$typeList} primary size",
            'badgeLabel' => 'Matches ' . $this->formatTypeList($matchingPrimaryTypes, short: true),
        ];
    }

    /**
     * @return array{modalLabel: string, badgeLabel: string}|null
     */
    private function submissionGalleryMismatchCueLabels(): ?array
    {
        $warningData = $this->gallerySizeWarningData();
        if (!$warningData) {
            return null;
        }

        return [
            'modalLabel' => "If added: gallery will include {$warningData['count']} image sizes{$warningData['summary']}",
            'badgeLabel' => 'Adds gallery size',
        ];
    }

    private function shouldShowGalleryMismatchAsCandidateCue(): bool
    {
        if ($this->canFixCurrentPrimary()) {
            return false;
        }

        return $this->canApproveToGallery()
            && $this->gallerySizeWarningData() !== null;
    }

    /**
     * @param Collection<int, ScreenshotType> $types
     */
    private function formatTypeList(Collection $types, bool $short = false): string
    {
        $labels = $types
            ->map(fn (ScreenshotType $type): string => $type->label())
            ->values();

        return $short
            ? $labels->implode('/')
            : $labels->join(', ', ' and ');
    }
}
