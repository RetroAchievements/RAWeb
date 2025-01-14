import { render, screen } from '@/test';
import { createGameSuggestionEntry } from '@/test/factories';

import { SuggestionReasonCell } from './SuggestionReasonCell';

describe('Component: SuggestionReasonCell', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'random' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the suggestion reason is shared-hub, renders the SharedHubReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'shared-hub' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('shared-hub-reason')).toBeVisible();
  });

  it('given the suggestion reason is similar-game, renders the SimilarGameReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'similar-game' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('similar-game-reason')).toBeVisible();
  });

  it('given the suggestion reason is random, renders the RandomReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'random' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('random-reason')).toBeVisible();
  });

  it('given the suggestion reason is want-to-play, renders the WantToPlayReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'want-to-play' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('want-to-play-reason')).toBeVisible();
  });

  it('given the suggestion reason is revised, renders the RevisedReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'revised' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('revised-reason')).toBeVisible();
  });

  it('given the suggestion reason is common-players, renders the CommonPlayersReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'common-players' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('common-players-reason')).toBeVisible();
  });

  it('given the suggestion reason is shared-author, renders the SharedAuthorReason component', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={createGameSuggestionEntry({ suggestionReason: 'shared-author' })}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('shared-author-reason')).toBeVisible();
  });

  it('given an unknown suggestion reason, renders nothing', () => {
    // ARRANGE
    render(
      <SuggestionReasonCell
        originalRow={{
          // @ts-expect-error Testing invalid reason
          suggestionReason: 'unknown-reason',
        }}
        sourceGame={null}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId(/reason/i)).not.toBeInTheDocument();
  });
});
