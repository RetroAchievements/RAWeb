import { render, screen } from '@/test';
import { createGame, createGameListEntry, createGameListEntryStats } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { buildMasteryTimeColumnDef } from './buildMasteryTimeColumnDef';

const dataTableColumnHeaderMock = vi.fn<(props: any) => void>();

vi.mock('../../components/DataTableColumnHeader', () => ({
  DataTableColumnHeader: (props: any) => {
    dataTableColumnHeaderMock(props);

    return <div>Mock Header</div>;
  },
}));

describe('Util: buildMasteryTimeColumnDef', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns a column definition with the expected fields', () => {
    // ARRANGE

    // ACT
    const columnDef = buildMasteryTimeColumnDef({ t_label: 'Time to Master' as TranslatedString });

    // ASSERT
    expect(columnDef.id).toEqual('medianTimeToMasterHardcore');
    expect((columnDef as any).accessorKey).toEqual('game');
    expect(columnDef.meta).toEqual({
      t_label: 'Time to Master',
      align: 'right',
      sortType: 'quantity',
      Icon: expect.any(Function),
    });
  });

  it('renders a header', () => {
    // ARRANGE
    const columnDef = buildMasteryTimeColumnDef({ t_label: 'Time to Master' as TranslatedString });
    const HeaderComponent = columnDef.header as any;
    const table = {} as any;

    const column = {
      columnDef: {
        id: 'medianTimeToMasterHardcore',
        meta: { t_label: 'Time to Master', align: 'right' },
      },
      getCanSort: () => false,
      getIsSorted: () => false,
      getCanHide: () => false,
    } as any;

    // ACT
    render(<HeaderComponent column={column} table={table} />);

    // ASSERT
    expect(screen.getByText('Mock Header')).toBeVisible();
    expect(dataTableColumnHeaderMock).toHaveBeenCalledWith(
      expect.objectContaining({
        column,
        table,
        tableApiRouteName: 'api.game.index',
      }),
    );
  });

  it('given a game has fewer than 5 masteries, renders a dash', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame(),
      gameListStats: createGameListEntryStats({
        coreSetTimesCompletedHardcore: 3,
        coreSetMedianTimeToCompleteHardcore: 3600,
      }),
    });

    const columnDef = buildMasteryTimeColumnDef({ t_label: 'Time to Master' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    // ACT
    render(<CellComponent row={{ original: gameListEntry }} />);

    // ASSERT
    expect(screen.getByText('-')).toBeVisible();
  });

  it('given game list stats are missing, renders a dash', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame(),
      gameListStats: null,
    });

    const columnDef = buildMasteryTimeColumnDef({ t_label: 'Time to Master' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    // ACT
    render(<CellComponent row={{ original: gameListEntry }} />);

    // ASSERT
    expect(screen.getByText('-')).toBeVisible();
  });

  it('given a game has enough mastery data, renders the formatted duration', () => {
    // ARRANGE
    const gameListEntry = createGameListEntry({
      game: createGame(),
      gameListStats: createGameListEntryStats({
        coreSetTimesCompletedHardcore: 5,
        coreSetMedianTimeToCompleteHardcore: 1234,
      }),
    });

    const columnDef = buildMasteryTimeColumnDef({ t_label: 'Time to Master' as TranslatedString });
    const CellComponent = columnDef.cell as any;

    // ACT
    render(<CellComponent row={{ original: gameListEntry }} />);

    // ASSERT
    expect(screen.getByText('20m')).toBeVisible();
  });
});
