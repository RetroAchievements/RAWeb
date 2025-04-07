import { AnimatePresence } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, memo, useEffect, useRef, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuChevronDown, LuSave } from 'react-icons/lu';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { Embed } from '@/common/components/Embed/Embed';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading/GameHeading';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { HashesList } from './HashesList';

export const HashesMainRoot: FC = memo(() => {
  const { can, game, hashes, incompatibleHashes, untestedHashes, patchRequiredHashes } =
    usePageProps<App.Platform.Data.GameHashesPageProps>();

  const { t } = useTranslation();
  const hasOtherHashes =
    incompatibleHashes?.length || untestedHashes?.length || patchRequiredHashes?.length;

  const [isOpen, setIsOpen] = useState(false);
  const contentRef = useRef<HTMLDivElement>(null);
  const [contentHeight, setContentHeight] = useState(0);

  useEffect(() => {
    if (contentRef.current) {
      setContentHeight(contentRef.current.offsetHeight);
    }
  }, [isOpen]);

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
                components={{ 1: <a href={`/viewtopic.php?t=${game.forumTopicId}`} /> }}
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

        {hasOtherHashes ? (
          <BaseCollapsible open={isOpen} onOpenChange={setIsOpen}>
            <BaseCollapsibleTrigger asChild>
              <BaseButton
                size="sm"
                className={cn(isOpen ? 'rounded-b-none border-transparent bg-embed' : null)}
              >
                {t('Other Known Hashes')}

                <LuChevronDown
                  className={cn(
                    'ml-1 size-4 transition-transform duration-300',
                    isOpen ? 'rotate-180' : 'rotate-0',
                  )}
                />
              </BaseButton>
            </BaseCollapsibleTrigger>

            <AnimatePresence initial={false}>
              {isOpen ? (
                <BaseCollapsibleContent forceMount asChild>
                  <m.div
                    initial={{ height: 0 }}
                    animate={{ height: contentHeight }}
                    exit={{ height: 0 }}
                    transition={{
                      duration: 0.3,
                      ease: [0.4, 0, 0.2, 1], // Custom easing curve for natural motion.
                    }}
                    className="overflow-hidden"
                  >
                    <div ref={contentRef} className="bg-embed p-4">
                      {patchRequiredHashes?.length ? (
                        <div className="flex flex-col gap-1">
                          <p>{t('These game file hashes require a patch to be compatible.')}</p>

                          <HashesList hashes={patchRequiredHashes} />
                        </div>
                      ) : null}

                      {untestedHashes?.length ? (
                        <div className="flex flex-col gap-1">
                          <p>
                            {t(
                              'These game file hashes are recognized, but it is unknown whether or not they are compatible.',
                            )}
                          </p>

                          <HashesList hashes={untestedHashes} />
                        </div>
                      ) : null}

                      {incompatibleHashes?.length ? (
                        <div className="flex flex-col gap-1">
                          <p>{t('These game file hashes are known to be incompatible.')}</p>

                          <HashesList hashes={incompatibleHashes} />
                        </div>
                      ) : null}
                    </div>
                  </m.div>
                </BaseCollapsibleContent>
              ) : null}
            </AnimatePresence>
          </BaseCollapsible>
        ) : null}

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
