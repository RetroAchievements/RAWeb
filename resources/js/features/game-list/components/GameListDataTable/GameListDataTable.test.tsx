import type { ColumnDef, SortingState } from '@tanstack/react-table';
import { getCoreRowModel, getSortedRowModel, useReactTable } from '@tanstack/react-table';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createGame, createGameListEntry, createSystem } from '@/test/factories';

import { buildHasActiveOrInReviewClaimsColumnDef } from '../../utils/column-definitions/buildHasActiveOrInReviewClaimsColumnDef';
import { buildLastUpdatedColumnDef } from '../../utils/column-definitions/buildLastUpdatedColumnDef';
import { buildNumUnresolvedTicketsColumnDef } from '../../utils/column-definitions/buildNumUnresolvedTicketsColumnDef';
import { buildNumVisibleLeaderboardsColumnDef } from '../../utils/column-definitions/buildNumVisibleLeaderboardsColumnDef';
import { buildPlayersTotalColumnDef } from '../../utils/column-definitions/buildPlayersTotalColumnDef';
import { buildRetroRatioColumnDef } from '../../utils/column-definitions/buildRetroRatioColumnDef';
import { buildSystemColumnDef } from '../../utils/column-definitions/buildSystemColumnDef';
import { buildTitleColumnDef } from '../../utils/column-definitions/buildTitleColumnDef';
import { GameListDataTable } from './GameListDataTable';

dayjs.extend(utc);

interface TestHarnessProps {
  columns?: ColumnDef<App.Platform.Data.GameListEntry>[];
  data?: App.Platform.Data.GameListEntry[];
  sorting?: SortingState;
  isLoading?: boolean;
}

// We need to instantiate props with a hook, so a test harness is required.
const TestHarness: FC<TestHarnessProps> = ({
  data = [],
  sorting = [],
  columns = [],
  isLoading = false,
}) => {
  if (!columns.length) {
    columns = [
      buildTitleColumnDef({ t_label: i18n.t('Title') }),
      buildSystemColumnDef({ t_label: i18n.t('System') }),
    ];
  }

  const table = useReactTable({
    columns,
    data,
    state: { sorting },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
  });

  return <GameListDataTable table={table} isLoading={isLoading} />;
};

describe('Component: GameListDataTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestHarness />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  describe('Column Definitions', () => {
    it("given the game list entry's game has a null system, does not crash", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        system: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      const { container } = render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildSystemColumnDef({ t_label: i18n.t('System') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(container).toBeTruthy();
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
    });

    it("given the game list entry's game has a valid system, displays the system chip", () => {
      // ARRANGE
      const system = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const game = createGame({ system, title: 'Super Mario Bros.' });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildSystemColumnDef({ t_label: i18n.t('System') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/super mario bros/i)).toBeVisible();
      expect(screen.getByText(/nes/i)).toBeVisible();
    });

    it("given the game list entry's game has an undefined weighted points value, safely falls back to zero", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        pointsTotal: 100,
        pointsWeighted: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildRetroRatioColumnDef({
              t_label: i18n.t('Rarity'),
              strings: { t_none: i18n.t('none') },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText(/×0.00/i)).toBeVisible();
    });

    it("given the game list entry's game has a truthy weighted points value, correctly calculates the Rarity", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        pointsTotal: 100,
        pointsWeighted: 200, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildRetroRatioColumnDef({
              t_label: i18n.t('Rarity'),
              strings: { t_none: i18n.t('none') },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText(/×2.00/i)).toBeVisible();
    });

    it("given the game list entry's game has an undefined points value, displays a fallback string", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        pointsTotal: undefined, // !!
        pointsWeighted: undefined,
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildRetroRatioColumnDef({
              t_label: i18n.t('Rarity'),
              strings: { t_none: i18n.t('none') },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText(/none/i)).toBeVisible();
    });

    it("given the game list entry's game has an undefined players total value, displays zero", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        playersTotal: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildPlayersTotalColumnDef({ t_label: i18n.t('Players') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('0')).toBeVisible();
    });

    it("given the game list entry's game has a defined players total value, displays the players total value", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        playersTotal: 1234, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildPlayersTotalColumnDef({ t_label: i18n.t('Players') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('1,234')).toBeVisible();
    });

    it("given the game list entry's game has an undefined visible leaderboards value, displays zero", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        numVisibleLeaderboards: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildNumVisibleLeaderboardsColumnDef({ t_label: i18n.t('Leaderboards') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('0')).toBeVisible();
    });

    it("given the game list entry's game has a defined visible leaderboards value, displays the visible leaderboards value", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        numVisibleLeaderboards: 1234, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildNumVisibleLeaderboardsColumnDef({ t_label: i18n.t('Leaderboards') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('1,234')).toBeVisible();
    });

    it("given the game list entry's game has an undefined unresolved tickets value, displays zero", () => {
      const game = createGame({
        title: 'Sonic the Hedgehog',
        numUnresolvedTickets: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildNumUnresolvedTicketsColumnDef({ t_label: i18n.t('Tickets') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('0')).toBeVisible();
    });

    it("given the game list entry's game has a defined unresolved tickets value, displays the unresolved tickets value as an accessible link", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        numUnresolvedTickets: 1234, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildNumUnresolvedTicketsColumnDef({ t_label: i18n.t('Tickets') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByRole('link', { name: '1,234' })).toBeVisible();
    });

    it("given the game list entry's game has an undefined last updated at value, falls back to the current datetime", () => {
      // ARRANGE
      const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
      vi.setSystemTime(mockCurrentDate);

      const game = createGame({
        title: 'Sonic the Hedgehog',
        lastUpdated: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildLastUpdatedColumnDef({ t_label: i18n.t('Last Updated') }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
      expect(screen.getByText('May 8, 2024')).toBeVisible();
    });

    it("given the game list entry's game has an undefined active or in review claims value, falls back to false", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        hasActiveOrInReviewClaims: undefined, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildHasActiveOrInReviewClaimsColumnDef({
              t_label: i18n.t('Claimed'),
              strings: {
                t_yes: i18n.t('Yes'),
                t_description: i18n.t('One or more developers are currently working on this game.'),
              },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();

      expect(screen.getByText('-')).toBeVisible();
      expect(screen.queryByText(/yes/i)).not.toBeInTheDocument();
    });

    it("given the game list entry's game has active or in review claims, displays a label indicating this", () => {
      // ARRANGE
      const game = createGame({
        title: 'Sonic the Hedgehog',
        hasActiveOrInReviewClaims: true, // !!
      });
      const gameListEntry = createGameListEntry({ game });

      render(
        <TestHarness
          columns={[
            buildTitleColumnDef({ t_label: i18n.t('Title') }),
            buildHasActiveOrInReviewClaimsColumnDef({
              t_label: i18n.t('Claimed'),
              strings: {
                t_yes: i18n.t('Yes'),
                t_description: i18n.t('One or more developers are currently working on this game.'),
              },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();

      expect(screen.queryByText('-')).not.toBeInTheDocument();
      expect(screen.getByText(/yes/i)).toBeVisible();
    });

    it('given there are 9 visible columns, applies the correct overflow scroll styles', () => {
      // ARRANGE
      const columns = Array(9)
        .fill(null)
        .map((_, i) => ({
          id: `col${i}`,
          header: `Column ${i}`,
        }));

      render(<TestHarness columns={columns} data={[]} />);

      // ASSERT
      const tableContainer = screen.getByRole('table').parentElement;
      expect(tableContainer).toHaveClass('lg:!overflow-x-scroll');
      expect(tableContainer).not.toHaveClass('xl:!overflow-x-scroll');

      const headerRow = screen.getAllByRole('row')[0];
      expect(headerRow).toHaveClass('lg:!top-0');
      expect(headerRow).not.toHaveClass('xl:!top-0');
    });

    it('given there are 11 visible columns, applies the correct overflow scroll styles', () => {
      // ARRANGE
      const columns = Array(11)
        .fill(null)
        .map((_, i) => ({
          id: `col${i}`,
          header: `Column ${i}`,
        }));

      render(<TestHarness columns={columns} data={[]} />);

      // ASSERT
      const tableContainer = screen.getByRole('table').parentElement;
      expect(tableContainer).toHaveClass('lg:!overflow-x-scroll');
      expect(tableContainer).toHaveClass('xl:!overflow-x-scroll');

      const headerRow = screen.getAllByRole('row')[0];
      expect(headerRow).toHaveClass('lg:!top-0');
      expect(headerRow).toHaveClass('xl:!top-0');
    });
  });

  describe('Grouping', () => {
    it('given the table is not sorted by system, does not show system group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(<TestHarness data={gameListEntries} />);

      // ASSERT
      expect(screen.queryByText(/nes \(\d+ games?\)/i)).not.toBeInTheDocument();
      expect(screen.queryByText(/snes \(\d+ games?\)/i)).not.toBeInTheDocument();
      expect(screen.getByText(/super mario bros/i)).toBeVisible();
      expect(screen.getByText(/super mario world/i)).toBeVisible();
    });

    it('given the table is sorted by system and has multiple systems, shows system group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(<TestHarness data={gameListEntries} sorting={[{ id: 'system', desc: false }]} />);

      // ASSERT
      expect(screen.getByText(/nes\/famicom/i)).toBeVisible();
      expect(screen.getByText(/snes\/super famicom/i)).toBeVisible();
      expect(screen.getByText(/super mario bros/i)).toBeVisible();
      expect(screen.getByText(/super mario world/i)).toBeVisible();
    });

    it('given the table is sorted by system but only has one system, does not show system group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Legend of Zelda', system: nes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(<TestHarness data={gameListEntries} sorting={[{ id: 'system', desc: false }]} />);

      // ASSERT
      expect(screen.queryByText(/nes\/famicom \(\d+ games?\)/i)).not.toBeInTheDocument();

      expect(screen.getByText(/super mario bros/i)).toBeVisible();
      expect(screen.getByText(/legend of zelda/i)).toBeVisible();
    });

    it('given the table is sorted by system but only has multiple systems with multiple games, shows system group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Legend of Zelda', system: nes });
      const game3 = createGame({ title: 'Mega Man X', system: snes });
      const game4 = createGame({ title: 'Donkey Kong Country', system: snes });
      const game5 = createGame({ title: 'Donkey Kong Country 2', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
        createGameListEntry({ game: game3 }),
        createGameListEntry({ game: game4 }),
        createGameListEntry({ game: game5 }),
      ];

      render(<TestHarness data={gameListEntries} sorting={[{ id: 'system', desc: false }]} />);

      // ASSERT
      expect(screen.getByText('NES/Famicom')).toBeVisible();
      expect(screen.getByText('SNES/Super Famicom')).toBeVisible();
    });

    it('given the table is loading and was previously showing groups, preserves the group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      const { rerender } = render(
        <TestHarness data={gameListEntries} sorting={[{ id: 'system', desc: false }]} />,
      );

      // ACT
      rerender(
        <TestHarness
          data={gameListEntries}
          sorting={[{ id: 'system', desc: false }]}
          isLoading={true}
        />,
      );

      // ASSERT
      expect(screen.getByText('NES/Famicom')).toBeVisible();
      expect(screen.getByText('SNES/Super Famicom')).toBeVisible();
    });

    it('given the table is loading and was not previously showing groups, does not show group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(
        <TestHarness
          data={gameListEntries}
          sorting={[{ id: 'title', desc: false }]}
          isLoading={true}
        />,
      );

      // ASSERT
      expect(screen.queryByText('NES/Famicom')).not.toBeInTheDocument();
      expect(screen.queryByText('SNES/Super Famicom')).not.toBeInTheDocument();
    });

    it('given the table transitions to system sort while loading, does not show group headers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      const { rerender } = render(
        <TestHarness
          data={gameListEntries}
          sorting={[{ id: 'title', desc: false }]}
          isLoading={true}
        />,
      );

      // ACT
      rerender(
        <TestHarness
          data={gameListEntries}
          sorting={[{ id: 'system', desc: false }]}
          isLoading={true}
        />,
      );

      // ASSERT
      expect(screen.queryByText('NES/Famicom')).not.toBeInTheDocument();
      expect(screen.queryByText('SNES/Super Famicom')).not.toBeInTheDocument();
    });

    it('given system grouping is enabled, announces the change to screen readers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(<TestHarness data={gameListEntries} sorting={[{ id: 'system', desc: false }]} />);

      // ASSERT
      expect(screen.getByText(/games are now grouped by system/i)).toBeInTheDocument();
    });

    it('given system grouping is disabled, announces the change to screen readers', () => {
      // ARRANGE
      const nes = createSystem({ name: 'NES/Famicom', nameShort: 'NES' });
      const snes = createSystem({ name: 'SNES/Super Famicom', nameShort: 'SNES' });

      const game1 = createGame({ title: 'Super Mario Bros.', system: nes });
      const game2 = createGame({ title: 'Super Mario World', system: snes });

      const gameListEntries = [
        createGameListEntry({ game: game1 }),
        createGameListEntry({ game: game2 }),
      ];

      render(<TestHarness data={gameListEntries} sorting={[{ id: 'title', desc: false }]} />);

      // ASSERT
      expect(screen.getByText(/games are no longer grouped/i)).toBeInTheDocument();
    });
  });
});
