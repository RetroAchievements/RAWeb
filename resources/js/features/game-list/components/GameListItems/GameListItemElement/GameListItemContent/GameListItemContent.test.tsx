import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { render, screen } from '@/test';
import { createGameListEntry } from '@/test/factories';

import { GameListItemContent } from './GameListItemContent';

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
});
