import userEvent from '@testing-library/user-event';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { GameHeaderSlotContent } from './GameHeaderSlotContent';

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

describe('Component: GameHeaderSlotContent', () => {
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
      auth: null,
      isOnWantToPlayList: false,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    const { container } = render(<GameHeaderSlotContent />, { pageProps });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, still shows the Want to Play button', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      auth: null,
      isOnWantToPlayList: false,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // ASSERT
    expect(screen.getByRole('button', { name: /want to play/i })).toBeVisible();
  });

  it('given the game is not on the want to play list, the button indicates it is not pressed', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      auth: null,
      isOnWantToPlayList: false,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });

    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the game is on the want to play list, the button indicates it is pressed', () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      auth: null,
      isOnWantToPlayList: true,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // ASSERT
    const button = screen.getByRole('button', { name: /want to play/i });
    expect(button).toBeVisible();
    expect(button).toHaveAttribute('aria-pressed', 'true');
  });

  it('given the user is not authenticated and clicks Want to Play, redirects to login', async () => {
    // ARRANGE
    const game = createGame();
    const pageProps = {
      game,
      auth: null,
      isOnWantToPlayList: false,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // Store the original href setter.
    const hrefSetter = vi.fn();
    Object.defineProperty(window.location, 'href', {
      set: hrefSetter,
      configurable: true,
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to play/i }));

    // ASSERT
    expect(hrefSetter).toHaveBeenCalledWith(route('login'));
    expect(mockAddToGameList).not.toHaveBeenCalled();
  });

  it('given the user clicks Want to Play when the game is not in the list, adds it to the list', async () => {
    // ARRANGE
    const game = createGame({ id: 123, title: 'Super Mario Bros.' });
    const user = createAuthenticatedUser();
    const pageProps = {
      game,
      auth: { user },
      isOnWantToPlayList: false,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to play/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockAddToGameList).toHaveBeenCalledWith(
        123,
        'Super Mario Bros.',
        expect.objectContaining({
          userGameListType: 'play',
        }),
      );
    });

    // ... the button should optimistically update to show the check icon ...
    expect(screen.getByRole('button', { name: /want to play/i })).toHaveAttribute(
      'aria-pressed',
      'true',
    );
  });

  it('given the user clicks Want to Play when the game is in the list, removes it from the list', async () => {
    // ARRANGE
    const game = createGame({ id: 456, title: 'Sonic the Hedgehog' });
    const user = createAuthenticatedUser();
    const pageProps = {
      game,
      auth: { user },
      isOnWantToPlayList: true,
      isOnWantToDevList: false,
      ziggy: createZiggyProps(),
    };

    render(<GameHeaderSlotContent />, { pageProps });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /want to play/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockRemoveFromGameList).toHaveBeenCalledWith(
        456,
        'Sonic the Hedgehog',
        expect.objectContaining({
          userGameListType: 'play',
        }),
      );
    });

    // ... the button should optimistically update to show the plus icon ...
    expect(screen.getByRole('button', { name: /want to play/i })).toHaveAttribute(
      'aria-pressed',
      'false',
    );
  });
});
