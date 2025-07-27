import dayjs from 'dayjs';
import { type FC, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface MobileCreditDialogTriggerProps {
  achievementSetClaims: App.Platform.Data.AchievementSetClaim[];
  aggregateCredits: App.Platform.Data.AggregateAchievementSetCredits;
  artCreditUsers: App.Platform.Data.UserCredits[];
  codingCreditUsers: App.Platform.Data.UserCredits[];
  designCreditUsers: App.Platform.Data.UserCredits[];
}

export const MobileCreditDialogTrigger: FC<MobileCreditDialogTriggerProps> = ({
  achievementSetClaims,
  aggregateCredits,
  artCreditUsers,
  codingCreditUsers,
  designCreditUsers,
}) => {
  const { t } = useTranslation();

  const nonAuthorUniqueContributors = useMemo(() => {
    return [...artCreditUsers, ...codingCreditUsers, ...designCreditUsers].filter(
      (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
    );
  }, [artCreditUsers, codingCreditUsers, designCreditUsers]);

  // Dedupe logic credits with authors - it's a bit redundant.
  // TODO do this on the server to reduce initial props size
  const filteredLogicCredits = aggregateCredits.achievementsLogic.filter(
    (logicUser) =>
      !aggregateCredits.achievementsAuthors.some(
        (author) => author.displayName === logicUser.displayName,
      ),
  );

  // If there's no claims or credit to show, then bail.
  if (
    !achievementSetClaims.length &&
    !aggregateCredits.achievementsAuthors.length &&
    !nonAuthorUniqueContributors.length
  ) {
    return null;
  }

  return (
    <div className="flex w-full flex-col gap-x-1 sm:hidden">
      <BaseDialog>
        <BaseDialogTrigger asChild>
          <BaseButton size="sm" className="w-full gap-1.5 py-[15px]">
            {achievementSetClaims.length ? (
              <span>
                <LuWrench className="size-4" />
                <span className="sr-only">{t('Claimed')}</span>
              </span>
            ) : null}

            {!!achievementSetClaims.length && !!aggregateCredits.achievementsAuthors.length ? (
              <span>{'·'}</span>
            ) : null}

            {aggregateCredits.achievementsAuthors.length ? (
              <>
                {aggregateCredits.achievementsAuthors.length < 5 ? (
                  <UserAvatarStack
                    canLinkToUsers={false}
                    isOverlappingAvatars={false}
                    maxVisible={5}
                    size={20}
                    users={aggregateCredits.achievementsAuthors}
                  />
                ) : null}

                <span>
                  {t('{{val, number}} authors', {
                    count: aggregateCredits.achievementsAuthors.length,
                    val: aggregateCredits.achievementsAuthors.length,
                  })}
                </span>
              </>
            ) : null}

            {!!aggregateCredits.achievementsAuthors.length &&
            !!nonAuthorUniqueContributors.length ? (
              <span>{'·'}</span>
            ) : null}

            {nonAuthorUniqueContributors.length ? (
              <span className="flex items-center">
                <span>
                  {t('+{{val, number}} contributors', {
                    count: nonAuthorUniqueContributors.length,
                    val: nonAuthorUniqueContributors.length,
                  })}
                </span>
              </span>
            ) : null}
          </BaseButton>
        </BaseDialogTrigger>

        <BaseDialogContent className="block h-full">
          <BaseDialogHeader className="mb-6">
            <BaseDialogTitle>{t('Credits')}</BaseDialogTitle>
            <BaseDialogDescription className="text-balance text-xs">
              {t('This is a list of everyone known to have contributed to this achievement set.')}
            </BaseDialogDescription>
          </BaseDialogHeader>

          <div className="mb-8 flex h-[calc(100%-160px)] flex-col gap-6 overflow-auto">
            {achievementSetClaims.length ? (
              <TooltipCreditsSection headingLabel={t('Active Claims')}>
                {achievementSetClaims.map((claim) => (
                  <TooltipCreditRow
                    key={`tooltip-claim-${claim.user!.displayName}`}
                    canLinkToUser={true}
                    credit={{
                      avatarUrl: claim.user!.avatarUrl,
                      count: 0, // noop
                      dateCredited: new Date().toISOString(), // noop
                      displayName: claim.user!.displayName,
                    }}
                  >
                    {dayjs(claim.finishedAt!).isAfter(dayjs())
                      ? t('Expires {{date}}', { date: formatDate(claim.finishedAt!, 'l') })
                      : t('Expired {{date}}', { date: formatDate(claim.finishedAt!, 'l') })}
                  </TooltipCreditRow>
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsAuthors.length ? (
              <TooltipCreditsSection headingLabel={t('Achievement Authors')}>
                {aggregateCredits.achievementsAuthors.map((credit) => (
                  <TooltipCreditRow
                    key={`tooltip-author-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showAchievementCount={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementSetArtwork.length ? (
              <TooltipCreditsSection headingLabel={t('Game Badge Artwork')}>
                {aggregateCredits.achievementSetArtwork.map((credit) => (
                  <TooltipCreditRow
                    key={`tooltip-badge-artwork-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showCreditDate={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsArtwork.length ? (
              <TooltipCreditsSection headingLabel={t('Achievement Artwork')}>
                {aggregateCredits.achievementsArtwork.map((credit) => (
                  <TooltipCreditRow
                    key={`tooltip-ach-artwork-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showAchievementCount={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsMaintainers.length ? (
              <TooltipCreditsSection headingLabel={t('Achievement Maintainers')}>
                {aggregateCredits.achievementsMaintainers.map((credit) => (
                  <TooltipCreditRow
                    key={`maintainer-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showCreditDate={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {filteredLogicCredits.length ? (
              <TooltipCreditsSection headingLabel={t('Code Contributors')}>
                {filteredLogicCredits.map((credit) => (
                  <TooltipCreditRow
                    key={`logic-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showAchievementCount={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsDesign.length ? (
              <TooltipCreditsSection headingLabel={t('Achievement Design/Ideas')}>
                {aggregateCredits.achievementsDesign.map((credit) => (
                  <TooltipCreditRow
                    key={`design-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showAchievementCount={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsTesting.length ? (
              <TooltipCreditsSection headingLabel={t('Playtesters')}>
                {aggregateCredits.achievementsTesting.map((credit) => (
                  /**
                   * TODO show dates
                   * right now these are attached to achievements... it should probably be set credit
                   */
                  <TooltipCreditRow
                    key={`testing-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.hashCompatibilityTesting.length ? (
              <TooltipCreditsSection headingLabel={t('Hash Compatibility Testing')}>
                {aggregateCredits.hashCompatibilityTesting.map((credit) => (
                  <TooltipCreditRow
                    key={`hash-compatibility-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showCreditDate={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}

            {aggregateCredits.achievementsWriting.length ? (
              <TooltipCreditsSection headingLabel={t('Writing Contributions')}>
                {aggregateCredits.achievementsWriting.map((credit) => (
                  <TooltipCreditRow
                    key={`writing-credit-${credit.displayName}`}
                    canLinkToUser={true}
                    credit={credit}
                    showAchievementCount={true}
                  />
                ))}
              </TooltipCreditsSection>
            ) : null}
          </div>

          <BaseDialogFooter>
            <BaseDialogClose asChild>
              <BaseButton type="button">{t('Close')}</BaseButton>
            </BaseDialogClose>
          </BaseDialogFooter>
        </BaseDialogContent>
      </BaseDialog>
    </div>
  );
};
