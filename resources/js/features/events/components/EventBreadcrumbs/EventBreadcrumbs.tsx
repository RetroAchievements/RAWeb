import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { InertiaLink } from '@/common/components/InertiaLink';

interface EventBreadcrumbsProps {
  event: App.Platform.Data.Event;
  t_currentPageLabel?: TranslatedString;
}

export const EventBreadcrumbs: FC<EventBreadcrumbsProps> = ({ event, t_currentPageLabel }) => {
  const { t } = useTranslation();

  const CommunityEventsHubId = 4;

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('All Events')}>
            <BaseBreadcrumbLink asChild>
              {/* TODO eventually link to an events index page */}
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
              <BaseBreadcrumbPage>
                <BaseBreadcrumbPage>{event.legacyGame?.title}</BaseBreadcrumbPage>
              </BaseBreadcrumbPage>
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
