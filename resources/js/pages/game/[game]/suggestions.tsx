import { useHydrateAtoms } from 'jotai/utils';

import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const SimilarGameSuggestions: AppPage = () => {
  const { persistedViewPreferences } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  return (
    <>
      {/* TODO SEO */}

      <div className="container">
        <AppLayout.Main>
          <p>{'SimilarGameSuggestions'}</p>
        </AppLayout.Main>
      </div>
    </>
  );
};

SimilarGameSuggestions.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SimilarGameSuggestions;
