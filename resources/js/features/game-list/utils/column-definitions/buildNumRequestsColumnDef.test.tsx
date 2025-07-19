import { render, screen } from '@/test';
import { createGame, createGameListEntry } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { buildNumRequestsColumnDef } from './buildNumRequestsColumnDef';

describe('Util: buildNumRequestsColumnDef', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('is defined', () => {
    // ASSERT
    expect(buildNumRequestsColumnDef).toBeDefined();
  });

  it('returns a column definition with the expected properties', () => {
    // ACT
    const columnDef = buildNumRequestsColumnDef({ t_label: 'Requests' as TranslatedString });

    // ASSERT
    expect(columnDef.id).toEqual('numRequests');
    expect((columnDef as any).accessorKey).toEqual('numRequests');
    expect(columnDef.meta).toEqual({
      t_label: 'Requests',
      align: 'right',
      sortType: 'quantity',
      Icon: expect.any(Function),
    });
  });

  it('given the game has zero requests, renders the count as muted text without a link', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame({ numRequests: 0, id: 123 }),
    });

    const cellProps = {
      row: { original: gameListEntry },
      getValue: vi.fn(),
    };

    const columnDef = buildNumRequestsColumnDef({ t_label: 'Requests' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    render(<CellComponent {...cellProps} />);

    // ASSERT
    expect(screen.getByText('0')).toBeVisible();
    expect(screen.getByText('0')).toHaveClass('text-muted');
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given the game has requests, renders a link to the set requestors page', () => {
    // ARRANGE
    const gameId = 12345;
    const numRequests = 42;
    const gameListEntry = createGameListEntry({
      game: createGame({ numRequests, id: gameId }),
    });

    const cellProps = {
      row: { original: gameListEntry },
      getValue: vi.fn(),
    };

    const columnDef = buildNumRequestsColumnDef({ t_label: 'Requests' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    render(<CellComponent {...cellProps} />);

    // ASSERT
    const link = screen.getByRole('link', { name: /42/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', `/setRequestors.php?g=${gameId}`);
  });

  it('given the game data is missing, treats the request count as zero', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: undefined as any,
    });

    const cellProps = {
      row: { original: gameListEntry },
      getValue: vi.fn(),
    };

    const columnDef = buildNumRequestsColumnDef({ t_label: 'Requests' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    render(<CellComponent {...cellProps} />);

    // ASSERT
    expect(screen.getByText('0')).toBeVisible();
    expect(screen.getByText('0')).toHaveClass('text-muted');
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given the game has no numRequests property, treats it as zero', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: { id: 123 } as any, // !! Missing numRequests.
    });

    const cellProps = {
      row: { original: gameListEntry },
      getValue: vi.fn(),
    };

    const columnDef = buildNumRequestsColumnDef({ t_label: 'Requests' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    render(<CellComponent {...cellProps} />);

    // ASSERT
    expect(screen.getByText('0')).toBeVisible();
    expect(screen.getByText('0')).toHaveClass('text-muted');
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });
});
