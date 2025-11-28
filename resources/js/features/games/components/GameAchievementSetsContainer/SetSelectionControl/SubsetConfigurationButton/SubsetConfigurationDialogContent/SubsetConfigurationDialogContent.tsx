/* eslint-disable jsx-a11y/anchor-has-content -- this is fine when using the <Trans /> component */

import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseAlert } from '@/common/components/+vendor/BaseAlert';
import {
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SubsetConfigurationForm } from './SubsetConfigurationForm';

interface SubsetConfigurationDialogContentProps {
  configurableSets: App.Platform.Data.GameAchievementSet[];
  onSubmitSuccess: () => void;
}

export const SubsetConfigurationDialogContent: FC<SubsetConfigurationDialogContentProps> = ({
  configurableSets,
  onSubmitSuccess,
}) => {
  const { auth } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const isGloballyOptedOut = !!auth?.user.preferences.isGloballyOptedOutOfSubsets;

  const settingsUrl = `${route('settings.show')}#=:~:text=${t('Automatically opt in to all game sets')}`;

  return (
    <BaseDialogContent>
      <BaseDialogHeader className="pb-3">
        <BaseDialogTitle>{t('Subset Configuration')}</BaseDialogTitle>
        <BaseDialogDescription asChild>
          <BaseAlert variant="default">
            <p className="text-left text-xs text-neutral-400 light:text-neutral-800">
              <Trans
                i18nKey="<1>If subsets aren't working or if every subset still requires a patch</1>, make sure you're using the latest version of your emulator. If you're using RetroArch, make absolutely sure the emulator version is 1.22.1 or higher. Updating the RetroArch cores alone is not sufficient."
                components={{
                  1: <span className="font-bold text-neutral-300 light:text-neutral-800" />,
                }}
              />
            </p>
          </BaseAlert>
        </BaseDialogDescription>
      </BaseDialogHeader>

      <p>
        {isGloballyOptedOut ? (
          <Trans
            i18nKey="You have <1>globally opted out of all subsets</1>. Use the toggles below to opt in to specific sets for this game."
            components={{
              1: <a href={settingsUrl} target="_blank" />,
            }}
          />
        ) : (
          <Trans
            i18nKey="You have <1>globally opted in to all subsets</1>. Use the toggles below to opt out of specific sets for this game."
            components={{
              1: <a href={settingsUrl} target="_blank" />,
            }}
          />
        )}
      </p>

      <SubsetConfigurationForm
        configurableSets={configurableSets}
        onSubmitSuccess={onSubmitSuccess}
      />
    </BaseDialogContent>
  );
};
