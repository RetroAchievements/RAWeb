import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseCard } from '@/common/components/+vendor/BaseCard';
import { ManageButton } from '@/common/components/ManageButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useVisibleEmulators } from '../../hooks/useVisibleEmulators';
import { AllSystemsDialog } from '../AllSystemsDialog';
import { AvailableEmulatorsList } from '../AvailableEmulatorsList';
import { PlatformSelector } from '../PlatformSelector';
import { SearchEmulators } from '../SearchEmulators';
import { SortEmulators } from '../SortEmulators';
import { SystemSelector } from '../SystemSelector';

export const DownloadsMainRoot: FC = () => {
  const { can } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const { visibleEmulators } = useVisibleEmulators();

  return (
    <div className="flex flex-col">
      <AllSystemsDialog />

      <h1 className="flex w-full justify-between">
        <span>{t('Downloads')}</span>

        {can.manageEmulators ? <ManageButton href="/manage/emulators" /> : null}
      </h1>

      <div className="flex flex-col gap-6 sm:gap-10">
        <BaseCard className="flex flex-col gap-5 light:bg-white light:shadow-sm">
          <div className="grid lg:grid-cols-2">
            <SystemSelector />
            <PlatformSelector />
          </div>

          <div className="grid sm:grid-cols-2">
            <SearchEmulators />
            <SortEmulators />
          </div>
        </BaseCard>

        <div className="flex flex-col gap-3">
          <div className="flex w-full items-center justify-between gap-3">
            <h3 className="mb-0 border-b-0">{t('Available Emulators')}</h3>
            <p>
              {t('({{emulatorsCount, number}} found)', { emulatorsCount: visibleEmulators.length })}
            </p>
          </div>

          <AvailableEmulatorsList />
        </div>
      </div>
    </div>
  );
};
