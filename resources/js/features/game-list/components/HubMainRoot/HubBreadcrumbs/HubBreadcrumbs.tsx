import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface HubBreadcrumbsProps {
  breadcrumbs: Array<App.Platform.Data.GameSet>;
}

export const HubBreadcrumbs: FC<HubBreadcrumbsProps> = ({ breadcrumbs }) => {
  const { cleanBreadcrumbHubTitles } = useCleanBreadcrumbHubTitles();

  const seenPrefixes = new Set<string>();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          {breadcrumbs.map((breadcrumb, index) => {
            const currentTitle = cleanBreadcrumbHubTitles(breadcrumb.title!, seenPrefixes);

            return (
              <Fragment key={`crumb-${breadcrumb.id}`}>
                {index !== breadcrumbs.length - 1 ? (
                  <>
                    <BaseBreadcrumbItem aria-label={breadcrumb.title!}>
                      <BaseBreadcrumbLink asChild>
                        <a href={route('hub.show', { gameSet: breadcrumb.id })}>{currentTitle}</a>
                      </BaseBreadcrumbLink>
                    </BaseBreadcrumbItem>

                    <BaseBreadcrumbSeparator />
                  </>
                ) : null}

                {index === breadcrumbs.length - 1 ? (
                  <BaseBreadcrumbItem aria-label={breadcrumb.title!}>
                    <BaseBreadcrumbPage>{currentTitle}</BaseBreadcrumbPage>
                  </BaseBreadcrumbItem>
                ) : null}
              </Fragment>
            );
          })}
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};

function useCleanBreadcrumbHubTitles() {
  const { t } = useTranslation();

  const cleanBreadcrumbHubTitles = (title: string, seenPrefixes: Set<string>): string => {
    if (title === '[Central]') {
      return t('All Hubs');
    }

    const cleaned = cleanHubTitle(title);

    // Always strip these organizational prefixes first.
    const alwaysStripPrefixes = [
      'ASB -',
      'Central - ',
      'Events -',
      'Genre - ',
      'Meta - ',
      'Meta|DevComp - ',
      'Meta|QA - ',
      'Misc. - ',
      'Series - ',
      'Series Hacks - ',
      'Subgenre - ',
      'Subseries -',
    ];

    for (const prefix of alwaysStripPrefixes) {
      if (cleaned.startsWith(prefix)) {
        const stripped = cleaned.slice(prefix.length);
        const firstWord = stripped.split(' - ')[0];
        seenPrefixes.add(firstWord);

        return stripped;
      }
    }

    // For any other title, check if it matches a pattern like "Word" or "Word - Rest".
    const parts = cleaned.split(' - ');
    const firstWord = parts[0];

    if (parts.length === 1) {
      // Single word - mark it as seen.
      seenPrefixes.add(firstWord);

      return cleaned;
    }

    // Multi-part title (e.g., "Word - Rest").
    if (seenPrefixes.has(firstWord)) {
      // We've seen this word before, strip the prefix
      return parts.slice(1).join(' - ');
    }

    // First time seeing this word, mark it and keep the full title.
    seenPrefixes.add(firstWord);

    return cleaned;
  };

  return { cleanBreadcrumbHubTitles };
}
