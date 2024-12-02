import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { useGameBacklogState } from '../GameListItems/useGameBacklogState';
import { DataTableRowActions } from './DataTableRowActions';

vi.mock('../GameListItems/useGameBacklogState');

describe('Component: DataTableRowActions', () => {
  beforeEach(() => {
    vi.mocked(useGameBacklogState).mockReturnValue({
      isPending: false,
      toggleBacklog: vi.fn(),
      isInBacklogMaybeOptimistic: false,
    } as any);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockRow = {
      original: {
        game: createGame(),
        isInBacklog: false,
      },
    };

    const { container } = render(
      <DataTableRowActions row={mockRow as any} shouldAnimateBacklogIconOnChange={true} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given isInBacklog is undefined, defaults to false', () => {
    // ARRANGE
    const mockRow = {
      original: {
        game: createGame(),
        isInBacklog: undefined,
      },
    };

    render(<DataTableRowActions row={mockRow as any} shouldAnimateBacklogIconOnChange={true} />);

    // ASSERT
    expect(screen.getByLabelText(/add to want to play games/i)).toBeVisible();
  });
});
