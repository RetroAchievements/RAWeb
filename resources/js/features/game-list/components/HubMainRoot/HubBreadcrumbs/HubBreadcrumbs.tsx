import { type FC, Fragment } from 'react';
import { route } from 'ziggy-js';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { useCleanBreadcrumbHubTitles } from '@/common/hooks/useCleanBreadcrumbHubTitles';

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
