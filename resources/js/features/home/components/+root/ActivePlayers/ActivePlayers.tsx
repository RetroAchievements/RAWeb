import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { ActivePlayerFeed } from '@/common/components/ActivePlayerFeed';
import { usePageProps } from '@/common/hooks/usePageProps';

import { HomeHeading } from '../../HomeHeading';

dayjs.extend(utc);

export const ActivePlayers: FC = () => {
  const { activePlayers, persistedActivePlayersSearch } =
    usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <HomeHeading>{t('Active Players')}</HomeHeading>

      <ActivePlayerFeed
        initialActivePlayers={activePlayers}
        persistedSearchValue={persistedActivePlayersSearch ?? undefined}
      />
    </div>
  );
};
