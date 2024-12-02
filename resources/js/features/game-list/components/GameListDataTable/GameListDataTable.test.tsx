import type { ColumnDef } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

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
}

// We need to instantiate props with a hook, so a test harness is required.
const TestHarness: FC<TestHarnessProps> = ({ columns = [], data = [] }) => {
  const table = useReactTable({
    columns,
    data,
    getCoreRowModel: getCoreRowModel(),
  });

  return <GameListDataTable table={table} />;
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildSystemColumnDef({ t_label: 'System' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildSystemColumnDef({ t_label: 'System' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildRetroRatioColumnDef({ t_label: 'Rarity', strings: { t_none: 'none' } }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildRetroRatioColumnDef({ t_label: 'Rarity', strings: { t_none: 'none' } }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildRetroRatioColumnDef({ t_label: 'Rarity', strings: { t_none: 'none' } }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildPlayersTotalColumnDef({ t_label: 'Players' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildPlayersTotalColumnDef({ t_label: 'Players' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildNumVisibleLeaderboardsColumnDef({ t_label: 'Leaderboards' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildNumVisibleLeaderboardsColumnDef({ t_label: 'Leaderboards' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildNumUnresolvedTicketsColumnDef({ t_label: 'Tickets' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildNumUnresolvedTicketsColumnDef({ t_label: 'Tickets' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildLastUpdatedColumnDef({ t_label: 'Last Updated' }),
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildHasActiveOrInReviewClaimsColumnDef({
              t_label: 'Claimed',
              strings: { t_yes: 'yes', t_description: 'description' },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();

      expect(screen.getByText('-')).toBeVisible();
      expect(screen.queryByText('yes')).not.toBeInTheDocument();
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
            buildTitleColumnDef({ t_label: 'Title' }),
            buildHasActiveOrInReviewClaimsColumnDef({
              t_label: 'Claimed',
              strings: { t_yes: 'yes', t_description: 'description' },
            }),
          ]}
          data={[gameListEntry]}
        />,
      );

      // ASSERT
      expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();

      expect(screen.queryByText('-')).not.toBeInTheDocument();
      expect(screen.getByText('yes')).toBeVisible();
    });
  });
});
