/* eslint-disable jsx-a11y/anchor-has-content -- this is fine when using the <Trans /> component */

import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseAlert } from '@/common/components/+vendor/BaseAlert';
import {
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';

import { SubsetConfigurationForm } from './SubsetConfigurationForm';

interface SubsetConfigurationDialogContentProps {
  configurableSets: App.Platform.Data.GameAchievementSet[];
  onSubmitSuccess: () => void;
}

export const SubsetConfigurationDialogContent: FC<SubsetConfigurationDialogContentProps> = ({
  configurableSets,
  onSubmitSuccess,
}) => {
  const { t } = useTranslation();

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

      <p>{t('Select which sets will be active when you play the game.')}</p>

      <SubsetConfigurationForm
        configurableSets={configurableSets}
        onSubmitSuccess={onSubmitSuccess}
      />
    </BaseDialogContent>
  );
};
