import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { PersonalizedSuggestionsMainRoot } from '@/features/game-list/components/PersonalizedSuggestionsMainRoot';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const PersonalizedGameSuggestions: AppPage = () => {
  const { auth, persistedViewPreferences } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  return (
    <>
      <SEO
        title={t('Game Suggestions - {{user}}', { user: auth!.user.displayName })}
        description={`Personalized game suggestions for ${auth!.user.displayName}`}
      />

      <div className="container">
        <AppLayout.Main>
          <PersonalizedSuggestionsMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

PersonalizedGameSuggestions.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default PersonalizedGameSuggestions;
