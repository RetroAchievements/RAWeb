import type { ColumnFiltersState, SortingState } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { buildAchievementsPublishedColumnDef } from '@/features/game-list/utils/column-definitions/buildAchievementsPublishedColumnDef';
import { buildSystemColumnDef } from '@/features/game-list/utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '@/features/game-list/utils/column-definitions/buildTitleColumnDef';
import { render, screen } from '@/test';
import { createSystem } from '@/test/factories';

import { DataTableSuperFilter } from './DataTableSuperFilter';

vi.mock('@/common/components/GameAvatar', () => ({ GameAvatar: () => null }));
vi.mock('../RandomGameButton', () => ({ RandomGameButton: () => null }));

// Suppress vaul a11y warnings.
console.warn = vi.fn();

interface TestHarnessProps {
  columnFilters?: ColumnFiltersState;
  onColumnFiltersChange?: (filters: ColumnFiltersState) => void;
  onSortingChange?: (sorting: SortingState) => void;
  sorting?: SortingState;
}

// We need to instantiate props with a hook, so a test harness is required.
const TestHarness: FC<TestHarnessProps> = ({
  columnFilters = [],
  onColumnFiltersChange = () => {},
  onSortingChange = () => {},
  sorting = [],
}) => {
  const table = useReactTable({
    onColumnFiltersChange: onColumnFiltersChange as any,
    onSortingChange: onSortingChange as any,
    columns: [
      buildTitleColumnDef({ t_label: 'Title' }),
      buildSystemColumnDef({ t_label: 'System' }),
      buildAchievementsPublishedColumnDef({ t_label: 'Achievements' }),
    ],
    data: [],
    getCoreRowModel: getCoreRowModel(),
    state: {
      columnFilters,
      sorting,
    },
  });

  return <DataTableSuperFilter table={table} />;
};

describe('Component: DataTableSuperFilter', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();

    // This prevents vaul from exploding.
    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      transform: 'matrix(1, 0, 0, 1, 0, 0)',
      getPropertyValue: vi.fn(),
    } as unknown as CSSStyleDeclaration);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestHarness />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  describe('Button Label', () => {
    it('given the achievementsPublished filter value is "has" and there is no systems filter, displays the correct label', () => {
      // ARRANGE
      render(<TestHarness columnFilters={[{ id: 'achievementsPublished', value: 'has' }]} />);

      // ASSERT
      expect(screen.getByRole('button', { name: 'Playable, All Systems' })).toBeVisible();
    });

    it('given the achievementsPublished filter value is "none" and there is no systems filter, displays the correct label', () => {
      // ARRANGE
      render(<TestHarness columnFilters={[{ id: 'achievementsPublished', value: 'none' }]} />);

      // ASSERT
      expect(screen.getByRole('button', { name: 'Not Playable, All Systems' })).toBeVisible();
    });

    it('given the achievementsPublished filter value is not set and there is no systems filter, displays the correct label', () => {
      // ARRANGE
      render(<TestHarness />);

      // ASSERT
      expect(screen.getByRole('button', { name: 'All Games, All Systems' })).toBeVisible();
    });

    it('given a single system filter is set, displays the correct label', () => {
      // ARRANGE
      render(
        <TestHarness
          columnFilters={[
            { id: 'achievementsPublished', value: 'has' },
            { id: 'system', value: [5] },
          ]}
        />,
      );

      // ASSERT
      expect(screen.getByRole('button', { name: 'Playable, 1 System' })).toBeVisible();
    });

    it('given multiple system filters are set, displays the correct label', () => {
      // ARRANGE
      render(
        <TestHarness
          columnFilters={[
            { id: 'achievementsPublished', value: 'has' },
            { id: 'system', value: [5, 7] },
          ]}
        />,
      );

      // ASSERT
      expect(screen.getByRole('button', { name: 'Playable, 2 Systems' })).toBeVisible();
    });
  });

  describe('Drawer', () => {
    it('given the user taps the super filter button, the drawer appears', async () => {
      // ARRANGE
      render(<TestHarness columnFilters={[{ id: 'achievementsPublished', value: 'has' }]} />);

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      // ASSERT
      expect(screen.getByRole('dialog', { name: /customize view/i })).toBeVisible();
    });

    it('allows the user to set the achievements published filter value', async () => {
      // ARRANGE
      const onColumnFiltersChange = vi.fn();

      render(
        <TestHarness
          columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
          onColumnFiltersChange={onColumnFiltersChange}
        />,
      );

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      await userEvent.click(screen.getByRole('option', { name: 'No' }));

      // ASSERT
      const updateFn = onColumnFiltersChange.mock.calls[0][0];
      const newFilters = updateFn([{ id: 'achievementsPublished', value: 'has' }]);

      expect(newFilters).toEqual([{ id: 'achievementsPublished', value: ['none'] }]);
    });

    it('allows the user to set the systems filter value', async () => {
      // ARRANGE
      const onColumnFiltersChange = vi.fn();

      render<{ filterableSystemOptions: App.Platform.Data.System[] }>(
        <TestHarness
          columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
          onColumnFiltersChange={onColumnFiltersChange}
        />,
        {
          pageProps: {
            filterableSystemOptions: [
              createSystem({ id: 1, name: 'NES/Famicom' }),
              createSystem({ id: 2, name: 'Nintendo 64' }),
            ],
          },
        },
      );

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      await userEvent.click(screen.getByRole('option', { name: 'NES/Famicom' }));

      // ASSERT
      const updateFn = onColumnFiltersChange.mock.calls[0][0];
      const newFilters = updateFn([{ id: 'achievementsPublished', value: 'has' }]);

      expect(newFilters).toEqual([
        { id: 'achievementsPublished', value: 'has' },
        { id: 'system', value: ['1'] },
      ]);
    });

    it('allows the user to change the current sort order to an ascending sort', async () => {
      // ARRANGE
      const onSortingChange = vi.fn();

      render(
        <TestHarness
          columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
          onSortingChange={onSortingChange}
        />,
      );

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      await userEvent.click(screen.getByRole('combobox', { name: /sort/i }));
      await userEvent.click(screen.getAllByRole('option', { name: /achievements/i })[0]);

      // ASSERT
      const updateFn = onSortingChange.mock.calls[0][0];
      const newSort = updateFn();

      expect(newSort).toEqual([{ id: 'achievementsPublished', desc: true }]);
    });

    it('allows the user to change the current sort order to a descending sort', async () => {
      // ARRANGE
      const onSortingChange = vi.fn();

      render(
        <TestHarness
          columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
          onSortingChange={onSortingChange}
        />,
      );

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      await userEvent.click(screen.getByRole('combobox', { name: /sort/i }));
      await userEvent.click(screen.getAllByRole('option', { name: /achievements/i })[1]);

      // ASSERT
      const updateFn = onSortingChange.mock.calls[0][0];
      const newSort = updateFn();

      expect(newSort).toEqual([{ id: 'achievementsPublished', desc: false }]);
    });

    it('dispatches a tracking action on sort order change', async () => {
      // ARRANGE
      const mockPlausible = vi.fn();

      Object.defineProperty(window, 'plausible', {
        writable: true,
        value: mockPlausible,
      });

      const onSortingChange = vi.fn();

      render(
        <TestHarness
          columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
          onSortingChange={onSortingChange}
        />,
      );

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /playable/i }));

      await userEvent.click(screen.getByRole('combobox', { name: /sort/i }));
      await userEvent.click(screen.getAllByRole('option', { name: /achievements/i })[0]);

      // ASSERT
      expect(mockPlausible).toHaveBeenCalledOnce();
      expect(mockPlausible).toHaveBeenCalledWith('Game List Sort', {
        props: { order: '-achievementsPublished' },
      });
    });
  });

  describe('Sort State Handling', () => {
    it('given no sorting state exists, uses a fallback "title" sort', async () => {
      // ARRANGE
      render(<TestHarness sorting={[]} />, { pageProps: { filterableSystemOptions: [] } });

      // ACT
      await userEvent.click(screen.getByRole('button'));
      const sortSelect = screen.getByRole('combobox', { name: /sort/i });
      await userEvent.click(sortSelect);

      // ASSERT
      expect(screen.getAllByText('Title, Ascending (A - Z)')[0]).toBeVisible();
    });
  });
});
