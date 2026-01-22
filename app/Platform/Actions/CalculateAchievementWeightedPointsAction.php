<?php

declare(strict_types=1);

namespace App\Platform\Actions;

/**
 * Calculates weighted points (RetroPoints) for an achievement based on its rarity and game popularity.
 *
 * RetroPoints reward players for earning rare achievements. The core idea is simple: blend base
 * points (60%) with a rarity multiplier (40%), where rarity = gamePlayers / unlocks. If 1000
 * people played a game but only 10 unlocked an achievement, that's a 100x rarity multiplier.
 *
 * The original formula (RP = points * 0.6 + points * (gamePlayers / unlocks) * 0.4) had two
 * problems this implementation addresses:
 *
 * 1. OBSCURE GAMES WERE UNDERVALUED: For unpopular or notoriously difficult games, often only
 *    hardcore fans who already know the game well will play them. This makes achievements look
 *    "easy" (high unlock rate) even when they're genuinely difficult, because casual players
 *    never showed up to fail. The low-player boost corrects for this small sample size by
 *    giving games with fewer players a multiplier that tapers off logarithmically as the
 *    player count grows.
 *
 * 2. ULTRA-RARE ACHIEVEMENTS WERE OVERVALUED: In subsets of very popular games, achievements
 *    with only 1-3 unlocks can have astronomically inflated RetroPoints because the base game
 *    has tens of thousands of players. The ultra-rare dampener applies a logarithmic cap when
 *    the rarity ratio exceeds a threshold, preventing runaway inflation.
 *
 * Both adjustments are self-correcting: as more players engage with obscure games, the boost
 * diminishes. As more players earn rare achievements, the dampener effect lessens.
 *
 * @see https://github.com/RetroAchievements/RAWeb/discussions/3626
 */
class CalculateAchievementWeightedPointsAction
{
    /**
     * Minimum ranked players assumed for formula stability.
     * Prevents division issues and extreme multipliers in test environments.
     */
    public const MIN_RANKED_PLAYERS = 80_000;

    /**
     * The low-player boost stabilizes to 1.0 when a game reaches this fraction of total ranked
     * players. This makes the threshold dynamic - it scales with site growth rather than being
     * a fixed number. A game with 1% of the site's ranked players is considered to have enough
     * data for its rarity values to be trustworthy without adjustment.
     */
    public const STABLE_PLAYER_RATIO = 0.01;

    /**
     * The ultra-rare dampener activates when rarity exceeds this fraction of total players.
     * At 0.2% (~1 in 500 unlock rate), the achievement is considered "ultra-rare" and the
     * dampener prevents the rarity multiplier from growing linearly.
     *
     * Note: Earlier proposals suggested 5% or 1%, but testing showed 1% was effectively a no-op
     * since very few achievements had unlock rates below 1 in 1,000.
     */
    public const MAX_RARITY_RATIO = 0.002;

    /**
     * Balance between base points and rarity adjustment.
     * 40% of the final score comes from rarity-adjusted points, 60% from base points.
     * This prevents rarity from completely dominating the score.
     */
    public const ADJUSTMENT_WEIGHT = 0.4;

    /**
     * @param int $points the base points for the achievement
     * @param int $unlocks the number of hardcore unlocks for this achievement
     * @param int $gamePlayers the number of hardcore players for the game
     * @param int $allPlayers the total number of ranked players site-wide
     */
    public function execute(int $points, int $unlocks, int $gamePlayers, int $allPlayers): int
    {
        $unlocks = $unlocks ?: 1;
        $gamePlayers = $gamePlayers ?: 1;
        $allPlayers = max($allPlayers, self::MIN_RANKED_PLAYERS);

        // LOW-PLAYER BOOST
        // Games with few players get a multiplier that drops off logarithmically.
        // The `/9` divisor is chosen so the formula equals exactly 1.0 at the stable threshold.
        // Math: threshold/(threshold/9) = 9, then 1 + 9 = 10, and log10(10) = 1.0.
        $stablePlayerCount = $allPlayers * self::STABLE_PLAYER_RATIO;
        $playerCountAdjustment = max(1.0, 1.0 / log10(1 + ($gamePlayers / ($stablePlayerCount / 9))));

        // RARITY ADJUSTMENT
        // Base rarity is simply the ratio of total players to achievement unlockers.
        // Higher ratio = rarer achievement = higher multiplier.
        $rarityAdjustment = $gamePlayers / $unlocks;

        // ULTRA-RARE DAMPENER
        // For extremely rare achievements (common in subsets of popular games), cap the linear
        // growth with a logarithmic curve. This prevents a single-unlock achievement in a game
        // with 40,000 players from having an absurdly inflated score.
        $maximumRarityAdjustment = $allPlayers * self::MAX_RARITY_RATIO;
        if ($rarityAdjustment > $maximumRarityAdjustment) {
            $rarityAdjustment = $maximumRarityAdjustment * (1 + log10($rarityAdjustment / $maximumRarityAdjustment));
        }

        $finalAdjustment = $rarityAdjustment * $playerCountAdjustment;

        // Final formula: blend base points (60%) with rarity-adjusted points (40%).
        return (int) (
            $points * (1 - self::ADJUSTMENT_WEIGHT)
            + ($points * $finalAdjustment * self::ADJUSTMENT_WEIGHT)
        );
    }
}
