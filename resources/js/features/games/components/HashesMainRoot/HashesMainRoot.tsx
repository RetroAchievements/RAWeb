import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuSave } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { Embed } from '@/common/components/Embed/Embed';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameBreadcrumbs } from '../GameBreadcrumbs';
import { GameHeading } from '../GameHeading/GameHeading';
import { HashesList } from './HashesList';

export const HashesMainRoot: FC = () => {
  const { can, game, hashes } = usePageProps<App.Platform.Data.GameHashesPageProps>();

  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

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
            href={route('game.hash.manage', { game: game.id })}
            className={baseButtonVariants({
              size: 'sm',
              className: 'flex items-center gap-1 sm:max-w-fit',
            })}
          >
            <LuSave className="h-5 w-5" />
            <span className="">{t('Manage Hashes')}</span>
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
                components={{ 1: <GameForumTopicLink game={game} /> }}
              >
                {'Additional information for these hashes may be listed on '}
                <GameForumTopicLink game={game} />
                {'.'}
              </Trans>
            ) : null}{' '}
            <Trans
              i18nKey="Details on how the hash is generated for each system can be found <1>here</1>."
              components={{ 1: <GameIdentificationDetailsLink /> }}
            >
              {'Details on how the hash is generated for each system can be found '}
              <GameIdentificationDetailsLink />
              {'.'}
            </Trans>
          </p>
        </Embed>

        <div className="flex flex-col gap-1">
          <p>
            <Trans
              i18nKey="supportedGameFilesCountLabel"
              count={hashes.length}
              values={{ count: hashes.length }}
              components={{ 1: <HashesCountSpan hashesCount={hashes.length} /> }}
            >
              {hashes.length === 1 ? 'There is currently' : 'There are currently'}{' '}
              <span className="font-bold">{formatNumber(hashes.length)}</span>{' '}
              {hashes.length === 1
                ? 'supported game file hash registered for this game.'
                : 'supported game file hashes registered for this game.'}
            </Trans>
          </p>

          <HashesList />
        </div>
      </div>
    </div>
  );
};

interface GameForumTopicLinkProps {
  game: App.Platform.Data.Game;
}

const GameForumTopicLink: FC<GameForumTopicLinkProps> = ({ game }) => {
  return <a href={`/viewtopic.php?t=${game.forumTopicId}`}>{"the game's official forum topic"}</a>;
};

const GameIdentificationDetailsLink: FC = () => (
  <a
    href="https://docs.retroachievements.org/developer-docs/game-identification.html"
    target="_blank"
  >
    {'here'}
  </a>
);

interface HashesCountSpanProps {
  hashesCount: number;
}

const HashesCountSpan: FC<HashesCountSpanProps> = ({ hashesCount }) => {
  const { formatNumber } = useFormatNumber();

  return <span className="font-bold">{formatNumber(hashesCount)}</span>;
};
