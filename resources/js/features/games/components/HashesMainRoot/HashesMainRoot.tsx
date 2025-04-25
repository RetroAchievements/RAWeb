import { type FC, memo } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuSave } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { Embed } from '@/common/components/Embed/Embed';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading/GameHeading';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { HashesList } from './HashesList';
import { OtherHashesSection } from './OtherHashesSection';

export const HashesMainRoot: FC = memo(() => {
  const { can, game, hashes } = usePageProps<App.Platform.Data.GameHashesPageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <GameBreadcrumbs
        game={game}
        system={game.system}
        t_currentPageLabel={t('Supported Game Files')}
      />
      <GameHeading game={game}>{t('Supported Game Files')}</GameHeading>

      <div className="flex flex-col gap-5">
        {can.manageGameHashes ? (
          <a
            // For performance reasons, Filament routes are not handled by Ziggy's `route()` function.
            href={`/manage/games/${game.id}/hashes`}
            className={baseButtonVariants({
              size: 'sm',
              className: 'flex items-center gap-1 sm:max-w-fit',
            })}
          >
            <LuSave className="size-5" />
            <span>{t('Manage Hashes')}</span>
          </a>
        ) : null}

        <Embed className="flex flex-col gap-4">
          <p className="font-bold">
            {t("This page shows you what ROM hashes are compatible with this game's achievements.")}
          </p>

          <p>
            {game.forumTopicId ? (
              <Trans
                i18nKey="Additional information for these hashes may be listed on <1>the game's official forum topic</1>."
                components={{
                  1: <InertiaLink href={route('forum-topic.show', { topic: game.forumTopicId })} />,
                }}
              />
            ) : null}{' '}
            <Trans
              i18nKey="Details on how the hash is generated for each system can be found <1>here</1>."
              components={{
                1: (
                  <a
                    href="https://docs.retroachievements.org/developer-docs/game-identification.html"
                    target="_blank"
                  />
                ),
              }}
            />
          </p>
        </Embed>

        <div className="flex flex-col gap-1">
          <p>
            <Trans
              i18nKey="supportedGameFilesCountLabel"
              count={hashes.length}
              components={{ 1: <span className="font-bold" /> }}
              values={{ count: hashes.length }}
            />
          </p>

          <HashesList hashes={hashes} />
        </div>

        <OtherHashesSection />

        <div>
          <p className="text-center text-neutral-500">
            <Trans
              i18nKey="Have a translation or quality-of-life hack you'd like to see supported? Click <1>here</1> to learn how to volunteer as a compatibility tester."
              components={{
                1: (
                  <a
                    href="https://docs.retroachievements.org/guidelines/content/player-compatibility-testing.html"
                    target="_blank"
                  />
                ),
              }}
            />
          </p>
        </div>
      </div>
    </div>
  );
});
