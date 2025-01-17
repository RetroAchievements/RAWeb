import { router } from '@inertiajs/react';
import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDices, LuUser } from 'react-icons/lu';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { GameHeading } from '@/common/components/GameHeading';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameSuggestionsDataTable } from '../GameSuggestionsDataTable';

export const SimilarGameSuggestionsMainRoot: FC = memo(() => {
  const { sourceGame } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const { t } = useTranslation();

  const game = sourceGame as App.Platform.Data.Game;

  const handleReload = () => {
    router.reload({ only: ['paginatedGameListEntries'] });
  };

  return (
    <div>
      <GameHeading game={game}>{t('Game Suggestions')}</GameHeading>

      <div className="flex flex-col gap-2">
        <div className="flex justify-end rounded bg-embed p-2">
          <div className="flex gap-2">
            <InertiaLink
              href={route('game.suggestions.personalized')}
              className={baseButtonVariants({ size: 'sm', className: 'gap-1' })}
            >
              <LuUser className="size-4" />
              {t('See your full recommendation feed')}
            </InertiaLink>

            <BaseButton onClick={handleReload} size="sm" className="group gap-1">
              <LuDices className="size-4 transition-transform duration-100 group-hover:rotate-12" />
              <span className="hidden sm:inline">{t('Roll again')}</span>
            </BaseButton>
          </div>
        </div>

        <GameSuggestionsDataTable showSourceGame={false} />
      </div>
    </div>
  );
});
