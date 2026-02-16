import { AnimatePresence } from 'motion/react';
import * as m from 'motion/react-m';
import { type FC, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { useAnimatedCollapse } from '@/common/hooks/useAnimatedCollapse';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { splitAchievementChangelogEntries } from '../../utils/splitAchievementChangelogEntries';
import { AchievementChangelogEntry } from './AchievementChangelogEntry';

export const AchievementChangelog: FC = () => {
  const { changelog } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { postPromotion, prePromotion, created, isCreatedAsPromoted } = useMemo(
    () => splitAchievementChangelogEntries(changelog),
    [changelog],
  );

  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse<HTMLUListElement>();

  if (changelog.length === 0) {
    return <p className="text-neutral-400">{t('No changelog entries found.')}</p>;
  }

  // Without pre-promotion entries, there's nothing to collapse.
  if (prePromotion.length === 0) {
    return (
      <ul className="flex flex-col">
        {changelog.map((entry, index) => (
          <AchievementChangelogEntry
            key={`entry-${index}`}
            entry={entry}
            isCreatedAsPromoted={isCreatedAsPromoted}
          />
        ))}
      </ul>
    );
  }

  return (
    <ul className="flex flex-col">
      {postPromotion.map((entry, index) => (
        <AchievementChangelogEntry key={`entry-${index}`} entry={entry} />
      ))}

      {/* Visually aligned with entry content but without its own timeline dot. */}
      <li className="relative pb-6">
        <div className="absolute -bottom-1 left-[3px] top-0 w-px bg-neutral-700" />

        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className="ml-5 flex items-center gap-1 text-xs text-neutral-400 transition hover:text-neutral-200"
        >
          {t('Initial development')}

          <LuChevronDown
            className={cn(
              'size-3.5 transition-transform duration-300',
              isOpen ? 'rotate-180' : 'rotate-0',
            )}
          />
        </button>

        <AnimatePresence initial={false}>
          {isOpen ? (
            <m.div
              initial={{ height: 0 }}
              animate={{ height: contentHeight }}
              exit={{ height: 0 }}
              transition={{ duration: 0.3, ease: [0.4, 0, 0.2, 1] }}
              className="overflow-hidden"
            >
              <ul ref={contentRef} className="ml-5 flex flex-col rounded bg-embed p-3">
                {prePromotion.map((entry, index) => (
                  <AchievementChangelogEntry key={`pre-promo-${index}`} entry={entry} />
                ))}
              </ul>
            </m.div>
          ) : null}
        </AnimatePresence>
      </li>

      {created ? (
        <AchievementChangelogEntry entry={created} isCreatedAsPromoted={isCreatedAsPromoted} />
      ) : null}
    </ul>
  );
};
