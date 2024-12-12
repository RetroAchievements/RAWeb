import { render, screen } from '@/test';
import { createGame, createGameListEntry, createSystem } from '@/test/factories';

import { GameRow } from './GameRow';

// Suppress validateDOMNesting() errors.
console.error = vi.fn();

const createMockRow = (gameListEntry: App.Platform.Data.GameListEntry) => ({
  getVisibleCells: () => [
    {
      id: 'cell1',
      column: {
        columnDef: {
          cell: () => gameListEntry.game.title,
          meta: { align: 'left' },
        },
      },
      getContext: () => ({}),
    },
    {
      id: 'cell2',
      column: {
        columnDef: {
          cell: () => gameListEntry.game.system?.name,
          meta: { align: 'right' },
        },
      },
      getContext: () => ({}),
    },
  ],
});

describe('Component: GameRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
    const game = createGame({ system, title: 'Super Mario Bros.' });
    const gameListEntry = createGameListEntry({ game });
    const mockRow = createMockRow(gameListEntry);

    const { container } = render(<GameRow row={mockRow as any} shouldShowGroups={false} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given shouldShowGroups is true, renders with transparent background styles', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
    const game = createGame({ system, title: 'Super Mario Bros.' });
    const gameListEntry = createGameListEntry({ game });
    const mockRow = createMockRow(gameListEntry);

    render(<GameRow row={mockRow as any} shouldShowGroups={true} />);

    // ASSERT
    const rowEl = screen.getByRole('row');
    expect(rowEl).toHaveClass('!bg-transparent');
  });

  it('given shouldShowGroups is false, does not render transparent background styles', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
    const game = createGame({ system, title: 'Super Mario Bros.' });
    const gameListEntry = createGameListEntry({ game });
    const mockRow = createMockRow(gameListEntry);

    render(<GameRow row={mockRow as any} shouldShowGroups={false} />);

    // ASSERT
    const rowEl = screen.getByRole('row');
    expect(rowEl).not.toHaveClass('!bg-transparent');
  });

  it('given a cell with right alignment, renders with right alignment styles', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
    const game = createGame({ system, title: 'Super Mario Bros.' });
    const gameListEntry = createGameListEntry({ game });
    const mockRow = createMockRow(gameListEntry);

    render(<GameRow row={mockRow as any} shouldShowGroups={false} />);

    // ASSERT
    const cellEls = screen.getAllByRole('cell');

    expect(cellEls[1]).toHaveClass('text-right');
  });

  it('given a cell with left alignment, does not render right alignment styles', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
    const game = createGame({ system, title: 'Super Mario Bros.' });
    const gameListEntry = createGameListEntry({ game });
    const mockRow = createMockRow(gameListEntry);

    render(<GameRow row={mockRow as any} shouldShowGroups={false} />);

    // ASSERT
    const cellEls = screen.getAllByRole('cell');

    expect(cellEls[0]).not.toHaveClass('text-right');
  });
});
