import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseDrawerContent,
  BaseDrawerDescription,
  BaseDrawerFooter,
  BaseDrawerHeader,
  BaseDrawerTitle,
} from '@/common/components/+vendor/BaseDrawer';
import { GameAvatar } from '@/common/components/GameAvatar';
import { GameTitle } from '@/common/components/GameTitle';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { SystemChip } from '@/common/components/SystemChip';
import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';
import { gameListFieldIconMap } from '@/features/game-list/utils/gameListFieldIconMap';
import type { TranslatedString } from '@/types/i18next';

import type { useGameBacklogState } from '../../useGameBacklogState';
import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';

interface GameListItemDrawerContentProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  gameListEntry: App.Platform.Data.GameListEntry;
  onToggleBacklog: () => void;
}

export const GameListItemDrawerContent: FC<GameListItemDrawerContentProps> = ({
  backlogState,
  gameListEntry,
  onToggleBacklog,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const { game, playerGame } = gameListEntry;

  return (
    <BaseDrawerContent>
      <div className="mx-auto w-full max-w-sm overflow-hidden">
        <BaseDrawerHeader>
          <BaseDrawerTitle>{t('Game Details')}</BaseDrawerTitle>
          <BaseDrawerDescription className="sr-only">{game.title}</BaseDrawerDescription>
        </BaseDrawerHeader>

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
              <a
                href={route('game.show', { game: game.id })}
                className="z-20 text-balance px-1.5 text-center text-lg tracking-tight text-text"
              >
                <GameTitle title={game.title} />
              </a>

              {game.system ? (
                <SystemChip {...game.system} className="z-20 bg-black light:bg-neutral-200/70">
                  {game.system.name}
                </SystemChip>
              ) : null}
            </div>
          </div>

          <ul className="mb-5 flex w-full flex-col gap-3 px-2">
            <DrawerListItem t_label={t('Release Date')} Icon={gameListFieldIconMap.releasedAt}>
              {game.releasedAt ? (
                <p>{formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity)}</p>
              ) : (
                <p className="text-muted italic">{t('unknown')}</p>
              )}
            </DrawerListItem>

            <DrawerListItem
              t_label={t('Achievements')}
              Icon={gameListFieldIconMap.achievementsPublished}
            >
              {game.achievementsPublished ? (
                <p>{formatNumber(game.achievementsPublished)}</p>
              ) : (
                <p className="text-muted">{formatNumber(0)}</p>
              )}
            </DrawerListItem>

            <DrawerListItem t_label={t('Points')} Icon={gameListFieldIconMap.pointsTotal}>
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
            </DrawerListItem>

            <DrawerListItem t_label={t('Rarity')} Icon={gameListFieldIconMap.retroRatio}>
              {game.pointsTotal ? (
                <p>{buildGameRarityLabel(game.pointsTotal, game.pointsWeighted)}</p>
              ) : (
                <p className="text-muted italic">{t('none')}</p>
              )}
            </DrawerListItem>

            <DrawerListItem
              t_label={t('Players')}
              Icon={gameListFieldIconMap.playersTotal}
              hasBottomBorder={!!auth?.user}
            >
              {game.playersTotal ? (
                <p>{formatNumber(game.playersTotal)}</p>
              ) : (
                <p className="text-muted italic">{formatNumber(0)}</p>
              )}
            </DrawerListItem>

            {auth?.user ? (
              <DrawerListItem
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
              </DrawerListItem>
            ) : null}
          </ul>
        </div>
      </div>

      <BaseDrawerFooter>
        <div className="grid grid-cols-2 gap-3">
          <GameListItemDrawerBacklogToggleButton
            backlogState={backlogState}
            onToggle={onToggleBacklog}
          />

          {/* TODO after migrating the game page to Inertia, prefetch this link */}
          <a
            href={route('game.show', { game: gameListEntry.game.id })}
            className={baseButtonVariants({ variant: 'secondary' })}
          >
            {t('Open Game')}
          </a>
        </div>
      </BaseDrawerFooter>
    </BaseDrawerContent>
  );
};

interface DrawerListItemProps {
  children: ReactNode;
  Icon: IconType;
  t_label: TranslatedString;

  hasBottomBorder?: boolean;
}

const DrawerListItem: FC<DrawerListItemProps> = ({
  t_label,
  Icon,
  children,
  hasBottomBorder = true,
}) => {
  return (
    <li role="listitem" aria-label={t_label}>
      <div className="flex w-full justify-between">
        <div className="flex items-center gap-2 text-neutral-200 light:text-neutral-950">
          <Icon className="h-4 w-4" aria-hidden="true" />
          <p>{t_label}</p>
        </div>

        {children}
      </div>

      {hasBottomBorder ? (
        <hr role="separator" className="mt-3 border-neutral-800 light:border-neutral-300" />
      ) : null}
    </li>
  );
};
