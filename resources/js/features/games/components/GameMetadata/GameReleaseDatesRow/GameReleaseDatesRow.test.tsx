import { BaseTable, BaseTableBody } from '@/common/components/+vendor/BaseTable';
import { render, screen } from '@/test';
import { createGameRelease } from '@/test/factories';

import { GameReleaseDatesRow } from './GameReleaseDatesRow';

describe('Component: GameReleaseDatesRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={[]} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a single worldwide release, does not show the region code', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        region: 'worldwide',
        releasedAt: '2023-01-15T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/jan 15, 2023/i)).toBeVisible();
    expect(screen.queryByText(/ww/i)).not.toBeInTheDocument();
  });

  it('given multiple releases, shows region codes for each release', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'na',
        releasedAt: '2023-01-15T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 2,
        region: 'jp',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/na/i)).toBeVisible();
    expect(screen.getByText(/jp/i)).toBeVisible();
  });

  it('given releases with null, "other", and "worldwide" regions, displays them all as WW', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: null,
        releasedAt: '2023-01-01T00:00:00Z',
      }),
      createGameRelease({
        id: 2,
        region: 'other',
        releasedAt: '2023-02-01T00:00:00Z',
      }),
      createGameRelease({
        id: 3,
        region: 'worldwide',
        releasedAt: '2023-03-01T00:00:00Z',
      }),
      createGameRelease({
        id: 4,
        region: 'jp',
        releasedAt: '2023-04-01T00:00:00Z',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    const wwElements = screen.getAllByText(/ww/i);
    expect(wwElements).toHaveLength(3); // !! 3 regions normalized to WW

    expect(screen.getByText(/jp/i)).toBeVisible();
  });

  it('given releases with year granularity, displays only the year', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        region: 'jp',
        releasedAt: '2023-06-15T00:00:00Z',
        releasedAtGranularity: 'year',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/2023/)).toBeVisible();

    expect(screen.queryByText(/jun/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/15/)).not.toBeInTheDocument();
  });

  it('given releases with month granularity, displays month and year', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        region: 'eu',
        releasedAt: '2023-06-15T00:00:00Z',
        releasedAtGranularity: 'month',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/june 2023/i)).toBeVisible();
    expect(screen.queryByText(/15/)).not.toBeInTheDocument(); // !! Day should not be shown
  });

  it('displays releases in the order provided by the back-end', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'jp',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 2,
        region: 'na',
        releasedAt: '2023-06-15T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'eu',
        releasedAt: '2023-12-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameReleaseDatesRow releases={releases} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    const cells = screen.getAllByRole('cell');
    const contentCell = cells[1];

    expect(contentCell.textContent).toMatch(/JP.*Jan 1, 2023.*NA.*Jun 15, 2023.*EU.*Dec 1, 2023/i);
  });
});
