import type { FC } from 'react';

import { CommonPlayersReason } from './CommonPlayersReason';
import { RandomReason } from './RandomReason';
import { RevisedReason } from './RevisedReason';
import { SharedAuthorReason } from './SharedAuthorReason';
import { SharedHubReason } from './SharedHubReason';
import { SimilarGameReason } from './SimilarGameReason';
import { WantToPlayReason } from './WantToPlayReason';

interface SuggestionReasonCellProps {
  originalRow: App.Platform.Data.GameSuggestionEntry;
}

export const SuggestionReasonCell: FC<SuggestionReasonCellProps> = ({ originalRow }) => {
  const { suggestionContext, suggestionReason } = originalRow;

  const relatedAuthor = suggestionContext?.relatedAuthor as App.Data.User;
  const relatedGame = suggestionContext?.relatedGame as App.Platform.Data.Game;
  const relatedGameSet = suggestionContext?.relatedGameSet as App.Platform.Data.GameSet;
  const sourceGameKind =
    suggestionContext?.sourceGameKind as App.Platform.Services.GameSuggestions.Enums.SourceGameKind;

  switch (suggestionReason) {
    case 'shared-hub':
      return <SharedHubReason relatedGame={relatedGame} relatedGameSet={relatedGameSet} />;

    case 'similar-game':
      return <SimilarGameReason relatedGame={relatedGame} sourceGameKind={sourceGameKind} />;

    case 'random':
      return <RandomReason />;

    case 'want-to-play':
      return <WantToPlayReason />;

    case 'revised':
      return <RevisedReason />;

    case 'common-players':
      return <CommonPlayersReason relatedGame={relatedGame} sourceGameKind={sourceGameKind} />;

    case 'shared-author':
      return <SharedAuthorReason relatedAuthor={relatedAuthor} relatedGame={relatedGame} />;

    default:
      return null;
  }
};
