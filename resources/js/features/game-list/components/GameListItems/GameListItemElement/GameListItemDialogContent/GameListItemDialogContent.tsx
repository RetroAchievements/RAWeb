import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import { LuArrowBigRight, LuX } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { GameAvatar } from '@/common/components/GameAvatar';
import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { SystemChip } from '@/common/components/SystemChip';
import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { useFormatGameReleasedAt } from '@/common/hooks/useFormatGameReleasedAt';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import type { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import { gameListFieldIconMap } from '@/features/game-list/utils/gameListFieldIconMap';
import type { TranslatedString } from '@/types/i18next';

import { GameListItemDialogBacklogToggleButton } from './GameListItemDialogBacklogToggleButton';

interface GameListItemDialogContentProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  gameListEntry: App.Platform.Data.GameListEntry;
  onToggleBacklog: () => void;
}

export const GameListItemDialogContent: FC<GameListItemDialogContentProps> = ({
  backlogState,
  gameListEntry,
  onToggleBacklog,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();
  const { formatGameReleasedAt } = useFormatGameReleasedAt();
  const { formatNumber } = useFormatNumber();

  const { game, playerGame } = gameListEntry;

  return (
    <BaseDialogContent className="sm:max-w-md">
      <div className="mx-auto w-full max-w-sm">
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Game Details')}</BaseDialogTitle>
          <BaseDialogDescription className="sr-only">{game.title}</BaseDialogDescription>
        </BaseDialogHeader>

        <div className="flex flex-col gap-4 p-4">
          <div className="flex flex-col items-center gap-4">
            <div>
              <GameAvatar
                {...game}
                hasTooltip={false}
                showLabel={false}
                size={96}
                loading="eager"
                decoding="sync"
                shouldGlow={true}
              />
            </div>

            <div className="mb-3 flex flex-col items-center gap-1">
              {/*
               * We've made this a URL in the event that the user starts panic tapping.
               * But we deliberately don't "show" it as a URL with the text-link color.
               */}
              <InertiaLink
                href={route('game.show', { game: game.id })}
                className="z-20 text-balance px-1.5 text-center text-lg tracking-tight text-text"
              >
                <GameTitle title={game.title} />
              </InertiaLink>

              {game.system ? (
                <SystemChip {...game.system} className="z-20 bg-black light:bg-neutral-200/70">
                  {game.system.name}
                </SystemChip>
              ) : null}
            </div>
          </div>

          <ul className="flex w-full flex-col gap-3 px-2">
            <DialogListItem t_label={t('Release Date')} Icon={gameListFieldIconMap.releasedAt}>
              {game.releasedAt ? (
                <p>{formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity)}</p>
              ) : (
                <p className="text-muted italic">{t('unknown')}</p>
              )}
            </DialogListItem>

            <DialogListItem
              t_label={t('Achievements')}
              Icon={gameListFieldIconMap.achievementsPublished}
            >
              {game.achievementsPublished ? (
                <p>{formatNumber(game.achievementsPublished)}</p>
              ) : (
                <p className="text-muted">{formatNumber(0)}</p>
              )}
            </DialogListItem>

            <DialogListItem t_label={t('Points')} Icon={gameListFieldIconMap.pointsTotal}>
              {game.pointsTotal !== undefined ? (
                <p>
                  {formatNumber(game.pointsTotal)}{' '}
                  <WeightedPointsContainer isTooltipEnabled={false}>
                    {'('}
                    {formatNumber(game.pointsWeighted ?? 0)}
                    {')'}
                  </WeightedPointsContainer>
                </p>
              ) : (
                <p className="text-muted">{formatNumber(0)}</p>
              )}
            </DialogListItem>

            <DialogListItem t_label={t('Rarity')} Icon={gameListFieldIconMap.retroRatio}>
              {game.pointsTotal ? (
                <p>{buildGameRarityLabel(game.pointsTotal, game.pointsWeighted)}</p>
              ) : (
                <p className="text-muted italic">{t('none')}</p>
              )}
            </DialogListItem>

            <DialogListItem
              t_label={t('Players')}
              Icon={gameListFieldIconMap.playersTotal}
              hasBottomBorder={!!auth?.user}
            >
              {game.playersTotal ? (
                <p>{formatNumber(game.playersTotal)}</p>
              ) : (
                <p className="text-muted italic">{formatNumber(0)}</p>
              )}
            </DialogListItem>

            {auth?.user ? (
              <DialogListItem
                t_label={t('Progress')}
                Icon={gameListFieldIconMap.progress}
                hasBottomBorder={false}
              >
                <PlayerGameProgressBar
                  game={game}
                  playerGame={playerGame}
                  variant="unmuted"
                  href={null}
                  isTooltipEnabled={false}
                  progressClassName="bg-neutral-800"
                  showProgressPercentage={true}
                />
              </DialogListItem>
            ) : null}
          </ul>
        </div>
      </div>

      <BaseDialogFooter className="flex flex-col gap-3">
        <div className="flex w-full justify-center">
          <GameListItemDialogBacklogToggleButton
            backlogState={backlogState}
            onToggle={onToggleBacklog}
          />
        </div>

        <div className="grid w-full grid-cols-2 gap-3">
          <BaseDialogClose asChild>
            <BaseButton className="w-full gap-1">
              <LuX className="size-4" />
              {t('Close')}
            </BaseButton>
          </BaseDialogClose>

          {/* TODO after migrating the game page to Inertia, prefetch this link */}
          <InertiaLink
            href={route('game.show', { game: gameListEntry.game.id })}
            className={baseButtonVariants({ className: 'gap-1' })}
          >
            {t('Open Game')}
            <LuArrowBigRight className="size-4" />
          </InertiaLink>
        </div>
      </BaseDialogFooter>
    </BaseDialogContent>
  );
};

interface DialogListItemProps {
  children: ReactNode;
  Icon: IconType;
  t_label: TranslatedString;

  hasBottomBorder?: boolean;
}

const DialogListItem: FC<DialogListItemProps> = ({
  t_label,
  Icon,
  children,
  hasBottomBorder = true,
}) => {
  return (
    <li aria-label={t_label}>
      <div className="flex w-full justify-between">
        <div className="flex items-center gap-2 text-neutral-200 light:text-neutral-950">
          <Icon className="h-4 w-4" aria-hidden="true" />
          <p>{t_label}</p>
        </div>

        {children}
      </div>

      {hasBottomBorder ? <hr className="mt-3 border-neutral-800 light:border-neutral-300" /> : null}
    </li>
  );
};
