import { route } from 'ziggy-js';

import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { render, screen } from '@/test';
import { createGame, createGameListEntry, createSystem } from '@/test/factories';

import { GameListItemContent } from './GameListItemContent';

vi.mock('ziggy-js', () => ({
  route: vi.fn(() => ({
    current: vi.fn(() => 'some.other.route'),
  })),
}));

describe('Component: GameListItemContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog>
        <GameListItemContent
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

  it('given the current route is "system.game.index", does not display the system chip', () => {
    // ARRANGE
    vi.mocked(route).mockReturnValue({
      current: vi.fn(() => 'system.game.index'), // !!
    } as any);

    const gameListEntry = createGameListEntry({
      game: createGame({ system: createSystem({ nameShort: 'NES' }) }),
    });

    render(
      <BaseDialog>
        <GameListItemContent
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

  it('given the current route is not "system.game.index", displays the system chip when game has a system', () => {
    // ARRANGE
    vi.mocked(route).mockReturnValue({
      current: vi.fn(() => 'game.show'), // !! different route
    } as any);

    const gameListEntry = createGameListEntry({
      game: createGame({ system: createSystem({ nameShort: 'NES' }) }), // !! ensure the game has a system
    });

    render(
      <BaseDialog>
        <GameListItemContent
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
