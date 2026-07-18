import type { FC } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBell, LuKeyRound, LuShield, LuUser } from 'react-icons/lu';

import {
  BaseTabs,
  BaseTabsContent,
  BaseTabsList,
  BaseTabsTrigger,
} from '@/common/components/+vendor/BaseTabs';
import { usePageNavigationTabs } from '@/common/hooks/usePageNavigationTabs';
import { cn } from '@/common/utils/cn';

import { settingsTabAtom } from '../../state/settings.atoms';
import { AccountTabPanel } from '../AccountTabPanel';
import { ApplicationsTabPanel } from '../ApplicationsTabPanel';
import { NotificationsTabPanel } from '../NotificationsTabPanel';
import { ProfileTabPanel } from '../ProfileTabPanel';

type SettingsTab = App.Community.Enums.UserSettingsPageTab;

export const SettingsRoot: FC = () => {
  const { t } = useTranslation();

  const tabs = useMemo(
    () =>
      [
        {
          value: 'profile',
          label: t('Profile'),
          IconComponent: LuUser,
          panel: <ProfileTabPanel />,
        },
        {
          value: 'notifications',
          label: t('Notifications'),
          IconComponent: LuBell,
          panel: <NotificationsTabPanel />,
        },
        {
          value: 'account',
          label: t('Account'),
          IconComponent: LuShield,
          panel: <AccountTabPanel />,
        },
        {
          value: 'applications',
          label: t('Applications'),
          IconComponent: LuKeyRound,
          panel: <ApplicationsTabPanel />,
        },
      ] as const,
    [t],
  );
  const tabValues = useMemo(() => tabs.map(({ value }) => value), [tabs]);

  const { currentTab, setCurrentTab } = usePageNavigationTabs(
    settingsTabAtom,
    'profile',
    tabValues,
  );
  const panelHeadingRef = useRef<HTMLHeadingElement>(null);
  const [shouldFocusPanel, setShouldFocusPanel] = useState(false);

  useEffect(() => {
    if (!shouldFocusPanel) {
      return;
    }

    const timeoutId = window.setTimeout(() => {
      panelHeadingRef.current?.focus();
      setShouldFocusPanel(false);
    });

    return () => window.clearTimeout(timeoutId);
  }, [currentTab, shouldFocusPanel]);

  const handleValueChange = (value: string) => {
    setShouldFocusPanel(true);
    setCurrentTab(value as SettingsTab, { shouldPushHistory: true });
  };

  return (
    <BaseTabs value={currentTab} onValueChange={handleValueChange} className="block">
      <div className="w-full">
        <h1>{t('Settings')}</h1>

        <div className="-mx-2.5 mb-4 overflow-x-auto md:hidden">
          <BaseTabsList
            className={cn(
              'flex min-w-full justify-start rounded-none border-b border-neutral-600 bg-neutral-900 py-0',
              'light:bg-neutral-200/40',
            )}
          >
            {tabs.map(({ value, label }) => (
              <BaseTabsTrigger
                key={value}
                value={value}
                variant="underlined"
                className="transition-none"
              >
                {label}
              </BaseTabsTrigger>
            ))}
          </BaseTabsList>
        </div>

        <div className="grid gap-6 md:grid-cols-[16rem_minmax(0,1fr)] md:gap-10">
          <nav aria-label={t('Settings')} className="hidden md:block">
            <BaseTabsList
              aria-orientation="vertical"
              className={cn(
                'flex h-auto w-full flex-col items-stretch justify-start gap-1 rounded-md bg-transparent p-0',
                'light:bg-transparent',
              )}
            >
              {tabs.map(({ value, label, IconComponent }) => (
                <BaseTabsTrigger key={value} value={value} variant="sidebar">
                  <IconComponent aria-hidden={true} className="size-4 min-w-4" />
                  {label}
                </BaseTabsTrigger>
              ))}
            </BaseTabsList>
          </nav>

          <div className="min-w-0">
            {tabs.map(({ value, label, panel }) => (
              <BaseTabsContent key={value} value={value} className="mt-0">
                <h2 ref={panelHeadingRef} tabIndex={-1} className="sr-only">
                  {label}
                </h2>
                {panel}
              </BaseTabsContent>
            ))}
          </div>
        </div>
      </div>
    </BaseTabs>
  );
};
