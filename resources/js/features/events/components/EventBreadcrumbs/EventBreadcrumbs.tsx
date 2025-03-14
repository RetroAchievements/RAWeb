import type { FC } from 'react';
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

interface EventBreadcrumbsProps {
  event: App.Platform.Data.Event;
}

export const EventBreadcrumbs: FC<EventBreadcrumbsProps> = ({ event }) => {
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
            <BaseBreadcrumbPage>{event.legacyGame?.title}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
