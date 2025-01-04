import type { FC } from 'react';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { GameAvatar } from '@/common/components/GameAvatar';
import { UnlockedAtLabel } from '@/features/achievements/components/UnlockedAtLabel';
import { AvatarSize } from '@/common/models';

interface UnlockableAchievementAvatarProps {
    achievement: App.Platform.Data.Achievement,
    showGame?: boolean,
    imageSize?: AvatarSize,
}

export const UnlockableAchievementAvatar: FC<UnlockableAchievementAvatarProps> = ({
    achievement,
    showGame = false,
    imageSize = 48
}) => {
    return (
        <div className="flex gap-2 mb-2 items-center">
            {achievement.unlockedHardcoreAt ? (
                <AchievementAvatar
                    {...achievement}
                    showHardcoreUnlockBorder={true}
                    // TODO: showPointsInTitle={true}
                    showLabel={false}
                    size={imageSize}
                />
            ) : achievement.unlockedAt ? (
                <AchievementAvatar
                    {...achievement}
                    showHardcoreUnlockBorder={false}
                    // TODO: showPointsInTitle={true}
                    showLabel={false}
                    size={imageSize}
                />
            ) : (
                <AchievementAvatar
                    {...achievement}
                    showHardcoreUnlockBorder={false}
                    badgeUnlockedUrl={achievement.badgeLockedUrl}
                    // TODO: showPointsInTitle={true}
                    showLabel={false}
                    size={imageSize}
                />
            )}

            <div>
                <div className="flex gap-2 items-center">
                    <AchievementAvatar
                        {...achievement}
                        showImage={false}
                    />

                    {showGame && achievement.game ? (
                        <>
                            <span>from</span>
                            <GameAvatar
                                {...achievement.game}
                                showImage={false}
                            />
                        </>
                    ) : (
                        <>
                        </>
                    )}
                </div>

                <span>
                    {achievement.description}
                </span>

                {achievement.unlockedHardcoreAt ? (
                    <UnlockedAtLabel when={achievement.unlockedHardcoreAt} />
                ) : achievement.unlockedAt ? (
                    <UnlockedAtLabel when={achievement.unlockedAt} />
                ) : (
                    <>
                    </>
                )}
            </div>
        </div>
    );
};