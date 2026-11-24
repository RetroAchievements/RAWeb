import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseBreadcrumbEllipsis,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbSeparator,
} from '../+vendor/BaseBreadcrumb';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuItem,
  BaseDropdownMenuTrigger,
} from '../+vendor/BaseDropdownMenu';
import { InertiaLink } from '../InertiaLink';
import { cn } from '@/common/utils/cn';

interface RootBreadcrumbsProps {
  /**
   * Folds "All Games" and the system into one "..." menu when the trail is too long to fit on one line.
   */
  isCollapsed?: boolean;

  system?: App.Platform.Data.System;
}

/**
 * Renders "All Games > [System]".
 */
export const RootBreadcrumbs: FC<RootBreadcrumbsProps> = ({ isCollapsed = false, system }) => {
  const { t } = useTranslation();

  if (isCollapsed) {
    return (
      <BaseBreadcrumbItem>
        <BaseDropdownMenu>
          <BaseDropdownMenuTrigger
            aria-label={t('More')}
            className={cn(
              "flex items-center rounded-sm outline-hidden hover:text-neutral-200",
              "focus-visible:ring-2 focus-visible:ring-neutral-300 light:hover:text-neutral-950 light:focus-visible:ring-neutral-950"
            )}
          >
            <BaseBreadcrumbEllipsis className="size-4" />
          </BaseDropdownMenuTrigger>

          <BaseDropdownMenuContent align="start">
            <BaseDropdownMenuItem asChild className="cursor-pointer">
              <InertiaLink href={route('game.index')} prefetch="desktop-hover-only">
                {t('All Games')}
              </InertiaLink>
            </BaseDropdownMenuItem>

            {system ? (
              <BaseDropdownMenuItem asChild className="cursor-pointer">
                <InertiaLink
                  href={route('system.game.index', system.id)}
                  prefetch="desktop-hover-only"
                >
                  {system.name}
                </InertiaLink>
              </BaseDropdownMenuItem>
            ) : null}
          </BaseDropdownMenuContent>
        </BaseDropdownMenu>
      </BaseBreadcrumbItem>
    );
  }

  return (
    <>
      <BaseBreadcrumbItem aria-label={t('All Games')}>
        <BaseBreadcrumbLink asChild>
          <InertiaLink href={route('game.index')} prefetch="desktop-hover-only">
            {t('All Games')}
          </InertiaLink>
        </BaseBreadcrumbLink>
      </BaseBreadcrumbItem>

      {system ? (
        <>
          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={system.name}>
            <BaseBreadcrumbLink asChild>
              <InertiaLink
                href={route('system.game.index', system.id)}
                prefetch="desktop-hover-only"
              >
                {system.name}
              </InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>
        </>
      ) : null}
    </>
  );
};
