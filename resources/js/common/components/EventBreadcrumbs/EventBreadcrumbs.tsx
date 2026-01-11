import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useCleanBreadcrumbHubTitles } from '@/common/hooks/useCleanBreadcrumbHubTitles';
import type { TranslatedString } from '@/types/i18next';

interface EventBreadcrumbsProps {
  breadcrumbs?: Array<App.Platform.Data.GameSet>;
  event: App.Platform.Data.Event;
  t_currentPageLabel?: TranslatedString;
}

export const EventBreadcrumbs: FC<EventBreadcrumbsProps> = ({
  breadcrumbs,
  event,
  t_currentPageLabel,
}) => {
  const { t } = useTranslation();

  const { cleanBreadcrumbHubTitles } = useCleanBreadcrumbHubTitles();

  const CommunityEventsHubId = 4;

  // If breadcrumbs are available, render them with the event title at the end.
  if (breadcrumbs && breadcrumbs.length > 0) {
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
                  <BaseBreadcrumbItem aria-label={breadcrumb.title!}>
                    <BaseBreadcrumbLink href={route('hub.show', { gameSet: breadcrumb.id })}>
                      {currentTitle}
                    </BaseBreadcrumbLink>
                  </BaseBreadcrumbItem>

                  <BaseBreadcrumbSeparator />
                </Fragment>
              );
            })}

            <BaseBreadcrumbItem aria-label={event.legacyGame?.title}>
              {t_currentPageLabel ? (
                <BaseBreadcrumbLink href={route('event.show', { event: event.id })}>
                  <span>{event.legacyGame?.title}</span>
                </BaseBreadcrumbLink>
              ) : (
                <BaseBreadcrumbPage>{event.legacyGame?.title}</BaseBreadcrumbPage>
              )}
            </BaseBreadcrumbItem>

            {t_currentPageLabel ? (
              <>
                <BaseBreadcrumbSeparator />

                <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
                  <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
                </BaseBreadcrumbItem>
              </>
            ) : null}
          </BaseBreadcrumbList>
        </BaseBreadcrumb>
      </div>
    );
  }

  // Fall back to simple breadcrumbs if no hub breadcrumbs are available.
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('All Events')}>
            <BaseBreadcrumbLink asChild>
              <InertiaLink href={route('hub.show', { gameSet: CommunityEventsHubId })}>
                {t('All Events')}
              </InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={event.legacyGame?.title}>
            {t_currentPageLabel ? (
              <BaseBreadcrumbLink href={route('event.show', { event: event.id })}>
                <span>{event.legacyGame?.title}</span>
              </BaseBreadcrumbLink>
            ) : (
              <BaseBreadcrumbPage>{event.legacyGame?.title}</BaseBreadcrumbPage>
            )}
          </BaseBreadcrumbItem>

          {t_currentPageLabel ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
                <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
              </BaseBreadcrumbItem>
            </>
          ) : null}
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
