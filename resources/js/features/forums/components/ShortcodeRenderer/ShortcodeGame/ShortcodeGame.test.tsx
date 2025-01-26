import { persistedGamesAtom } from '@/features/forums/state/forum.atoms';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { ShortcodeGame } from './ShortcodeGame';

describe('Component: ShortcodeGame', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeGame gameId={1} />, {
      jotaiAtoms: [
        [persistedGamesAtom, []],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game ID is not found in persisted games, renders nothing', () => {
    // ARRANGE
    render(<ShortcodeGame gameId={999999} />, {
      jotaiAtoms: [
        [persistedGamesAtom, [createGame({ id: 1 })]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByTestId('game-embed')).not.toBeInTheDocument();
  });

  it('given the game ID is found in persisted games, renders the game avatar', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    render(<ShortcodeGame gameId={1} />, {
      jotaiAtoms: [
        [persistedGamesAtom, [game]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByTestId('game-embed')).toBeVisible();

    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
    expect(screen.getByText(/sega genesis/i)).toBeVisible();
    expect(screen.getByRole('link')).toBeVisible();
  });
});
