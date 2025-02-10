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
import { InertiaLink } from '@/common/components/InertiaLink';
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
            const parentTitle = index > 0 ? breadcrumbs[index - 1].title : null;
            const currentTitle = cleanBreadcrumbHubTitles(
              breadcrumb.title!,
              seenPrefixes,
              parentTitle,
            );

            return (
              <Fragment key={`crumb-${breadcrumb.id}`}>
                {index !== breadcrumbs.length - 1 ? (
                  <>
                    <BaseBreadcrumbItem aria-label={breadcrumb.title!}>
                      <BaseBreadcrumbLink href={route('hub.show', { gameSet: breadcrumb.id })}>
                        {currentTitle}
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

  const cleanBreadcrumbHubTitles = (
    title: string,
    seenPrefixes: Set<string>,
    parentTitle: string | null,
  ): string => {
    // For the central hub, just retitle it as "All Hubs" and bail.
    if (title === '[Central]' || title === 'Central') {
      return t('All Hubs');
    }

    const cleaned = cleanHubTitle(title);

    /**
     * "[DevQuest 021 Sets]: Homebrew Heaven" -> "21: Homebrew Heaven"
     * Extract the quest number and content after the bracket.
     * Format as "N: Title".
     */
    if (cleaned.includes('DevQuest') && cleaned.includes('Sets]')) {
      const match = cleaned.match(/DevQuest (\d+)/);
      const number = match ? parseInt(match[1], 10) : null;

      const parts = cleaned.split(']');
      if (parts.length > 1) {
        const content = parts[1].trim();

        return number ? `${number}: ${content}` : content;
      }
    }

    // Always strip organizational prefixes first.
    const alwaysStripPrefixes = [
      'ASB -',
      'Dev Events -',
      'Events -',
      'Genre - ',
      'Meta|Art - ',
      'Meta|DevComp - ',
      'Meta|QA - ',
      'Misc. - ',
      'Series Hacks -',
      'Subgenre - ',
      'Subseries -',
    ];

    // Check explicit prefixes first.
    for (const prefix of alwaysStripPrefixes) {
      if (cleaned.startsWith(prefix)) {
        const stripped = cleaned.slice(prefix.length).trim();
        const firstWord = stripped.split(' - ')[0];
        seenPrefixes.add(firstWord);

        return stripped;
      }
    }

    // Split the title into parts.
    const parts = cleaned.split(' - ');

    // If we have a parent title, check if any part of our
    // current title duplicates information from the parent.
    if (parentTitle) {
      const parentParts = cleanHubTitle(parentTitle).split(' - ');
      const lastParentPart = parentParts[parentParts.length - 1];

      // If the first part of our current title matches the
      // last part of the parent, we can safely remove it. It's duplicative.
      if (parts[0] === lastParentPart) {
        return parts.slice(1).join(' - ');
      }
    }

    // Handle single word case.
    if (parts.length === 1) {
      seenPrefixes.add(parts[0]);

      return cleaned;
    }

    // For multi-part titles, check if we've seen the prefix before.
    const firstWord = parts[0];
    if (seenPrefixes.has(firstWord)) {
      return parts.slice(1).join(' - ');
    }

    seenPrefixes.add(firstWord);

    return cleaned;
  };

  return { cleanBreadcrumbHubTitles };
}
