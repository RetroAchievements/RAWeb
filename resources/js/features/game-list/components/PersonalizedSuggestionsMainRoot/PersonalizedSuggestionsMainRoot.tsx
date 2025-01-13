import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDices } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameSuggestionsDataTable } from '../GameSuggestionsDataTable';

export const PersonalizedSuggestionsMainRoot: FC = memo(() => {
  const { auth } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const { t } = useTranslation();

  if (!auth?.user) {
    return null;
  }

  const handleReload = () => {
    router.reload({ only: ['paginatedGameListEntries'] });
  };

  return (
    <div>
      <UserHeading user={auth.user}>
        <span className="sm:hidden">{t('Game Suggestions')}</span>
        <span className="hidden sm:inline">{t('Personalized Game Suggestions')}</span>
      </UserHeading>

      <div className="flex flex-col gap-2">
        <div className="flex justify-end rounded bg-embed p-2">
          <BaseButton onClick={handleReload} size="sm" className="group gap-1">
            <LuDices className="size-4 transition-transform duration-100 group-hover:rotate-12" />
            <span className="hidden sm:inline">{t('Roll again')}</span>
          </BaseButton>
        </div>

        <GameSuggestionsDataTable />
      </div>
    </div>
  );
});
