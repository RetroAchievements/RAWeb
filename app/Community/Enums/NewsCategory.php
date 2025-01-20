<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum NewsCategory: string
{
    /**
     * Used for new achievement set releases and revisions.
     * Examples:
     * - "New set: Pokémon XD: Gale of Darkness"
     */
    case AchievementSet = "achievement-set";

    /**
     * Used for community updates, spotlights, milestones, and weekly topics.
     * Examples:
     * - "Come Celebrate 1 MILLION Users!"
     * - "Weekly Topic: Your First Achievement"
     * - "Community Spotlight: Top Developers of 2024"
     */
    case Community = "community";

    /**
     * Used for events, competitions, and special occasions.
     * Examples:
     * - "RetroAchievemas 2024 Event"
     * - "Achievement of the Week 2025 Begins"
     * - "Summer Games Done Quick Special Event"
     */
    case Events = "events";

    /**
     * Used for achievement guides and tutorials.
     * Examples:
     * - "Guide Showcase: Harvest Moon DS: Cute"
     * - "Achievement Guide: Final Fantasy VII"
     * - "How to Get Started Making Achievements"
     */
    case Guide = "guide";

    /**
     * Used for external media coverage and content featuring RetroAchievements.
     * Examples:
     * - "authorblues and Skybilz at AGDQ 2025"
     * - "RetroAchievements Featured on IGN"
     * - "Community Race: Mega Man X Any%"
     * - "New YouTube Series: Achievement Hunting"
     * - "RetroRGB Podcast Interview"
     */
    case Media = "media";

    /**
     * Used by the engineering team for a "What's New" section on the home page.
     */
    case SiteReleaseNotes = "site-release-notes";

    /**
     * Used for site maintenance, updates, and feature announcements.
     * Examples:
     * - "Upcoming Hardcore Restriction"
     * - "Planned Site Maintenance"
     */
    case Technical = "technical";
}
