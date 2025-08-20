import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { render, screen } from '@/test';
import { createGame, createGameListEntry, createSystem } from '@/test/factories';

import { GameListItemContent } from './GameListItemContent';

describe('Component: GameListItemContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog>
        <GameListItemContent
          apiRouteName="api.game.index"
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          gameListEntry={createGameListEntry()}
          isLastItem={false}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the sortFieldId is "progress", does not display a chip of interest', () => {
    // ARRANGE
    render(
      <BaseDialog>
        <GameListItemContent
          apiRouteName="api.game.index"
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          gameListEntry={createGameListEntry()}
          isLastItem={false}
          sortFieldId="progress" // !!
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.queryByTestId('progress-chip')).not.toBeInTheDocument();
  });

  it('given the apiRouteName is "api.system.game.index", does not display the system chip', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame({ system: createSystem({ nameShort: 'NES' }) }),
    });

    render(
      <BaseDialog>
        <GameListItemContent
          apiRouteName="api.system.game.index" // !!
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          gameListEntry={gameListEntry}
          isLastItem={false}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.queryByText(gameListEntry.game.system!.name)).not.toBeInTheDocument();
  });

  it('given the apiRouteName is not "api.system.game.index", displays the system chip when game has a system', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame({ system: createSystem({ nameShort: 'NES' }) }), // !! ensure the game has a system
    });

    render(
      <BaseDialog>
        <GameListItemContent
          apiRouteName="api.game.index" // !! different API route
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          gameListEntry={gameListEntry}
          isLastItem={false}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByText('NES')).toBeVisible();
  });
});
