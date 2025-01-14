import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SimilarGameSuggestionsMainRoot } from '@/features/game-list/components/SimilarGameSuggestionsMainRoot';

const SimilarGameSuggestions: AppPage = () => {
  const { sourceGame } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const { t } = useTranslation();

  const gameTitle = sourceGame!.title;

  return (
    <>
      <SEO
        title={t('Game Suggestions - {{gameTitle}}', { gameTitle })}
        description={`A list of random games that a user might want to play if they enjoyed ${gameTitle}`}
        ogImage={sourceGame?.badgeUrl}
      />

      <div className="container">
        <AppLayout.Main>
          <SimilarGameSuggestionsMainRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

SimilarGameSuggestions.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SimilarGameSuggestions;
