import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { SidebarDevelopmentSection } from './SidebarDevelopmentSection';

const mockAddToGameList = vi.fn();
const mockRemoveFromGameList = vi.fn();

vi.mock('@/common/hooks/useAddOrRemoveFromUserGameList', () => ({
  useAddOrRemoveFromUserGameList: () => ({
    addToGameList: mockAddToGameList,
    removeFromGameList: mockRemoveFromGameList,
    isPending: false,
  }),
}));

Object.defineProperty(window, 'location', {
  value: { href: '' },
  writable: true,
});

describe('Component: SidebarDevelopmentSection', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mockAddToGameList.mockResolvedValue(undefined);
    mockRemoveFromGameList.mockResolvedValue(undefined);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    const { container } = render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is not on the want to develop list, sets aria-pressed to false', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to develop/i });
    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the game is on the want to develop list, sets aria-pressed to true', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      isOnWantToDevList: true,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to develop/i });
    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'true');
  });

  it('given the user clicks the button when the game is not in the list, adds it to the develop list', async () => {
    // ARRANGE
    const game = createGame({ id: 123, title: 'Super Mario World' });
    const user = createAuthenticatedUser();
    const pageProps = {
      auth: { user },
      game,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to develop/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockAddToGameList).toHaveBeenCalledWith(
        123,
        'Super Mario World',
        expect.objectContaining({
          userGameListType: 'develop',
        }),
      );
    });

    // ... the button should optimistically update to show as pressed ...
    expect(screen.getByRole('button', { name: /want to develop/i })).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });

  it('given the user clicks the button when the game is in the list, removes it from the develop list', async () => {
    // ARRANGE
    const game = createGame({ id: 456, title: 'Donkey Kong Country' });
    const user = createAuthenticatedUser();
    const pageProps = {
      auth: { user },
      game,
      isOnWantToDevList: true,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to develop/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockRemoveFromGameList).toHaveBeenCalledWith(
        456,
        'Donkey Kong Country',
        expect.objectContaining({
          userGameListType: 'develop',
        }),
      );
    });

    // ... the button should optimistically update to show as not pressed ...
    expect(screen.getByRole('button', { name: /want to develop/i })).toHaveAttribute(
      'aria-pressed',
      'false',
    );
  });

  it('given the game already has achievements published, changes the button label to mention revisions instead', () => {
    // ARRANGE
    const game = createGame({ id: 123, title: 'Super Mario World', achievementsPublished: 80 });
    const user = createAuthenticatedUser();
    const pageProps = {
      auth: { user },
      game,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<SidebarDevelopmentSection />, { pageProps });

    // ASSERT
    expect(screen.queryByRole('button', { name: /want to develop/i })).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /want to revise/i })).toBeVisible();
  });
});
