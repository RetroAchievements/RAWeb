import { render, screen } from '@/test';
import { createGameRelease } from '@/test/factories';

import { GameReleaseDatesRow } from './GameReleaseDatesRow';

describe('Component: GameReleaseDatesRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameReleaseDatesRow releases={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a single worldwide release, does not show the region code', () => {
    // ARRANGE
    const release = createGameRelease({
      region: 'worldwide',
      releasedAt: '2023-01-15T00:00:00Z',
      releasedAtGranularity: 'day',
    });

    render(<GameReleaseDatesRow releases={[release]} />);

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

    render(<GameReleaseDatesRow releases={releases} />);

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

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    const wwElements = screen.getAllByText(/ww/i);
    expect(wwElements).toHaveLength(1);

    expect(screen.getByText(/jp/i)).toBeVisible();
  });

  it('given multiple releases for the same region, only shows the earliest release', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'na',
        releasedAt: '2023-06-15T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 2,
        region: 'na',
        releasedAt: '2023-01-15T00:00:00Z', // !! earlier date
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'na',
        releasedAt: '2023-12-15T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    // ... should only show the January release ...
    expect(screen.getByText(/jan 15, 2023/i)).toBeVisible();

    expect(screen.queryByText(/jun 15, 2023/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/dec 15, 2023/i)).not.toBeInTheDocument();
  });

  it('given releases with different dates, sorts them chronologically', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'eu',
        releasedAt: '2023-12-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 2,
        region: 'na',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'jp',
        releasedAt: '2023-06-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    const allTextContent = screen.getByRole('cell', { name: /na.*jp.*eu/i });

    // ... the text should appear in chronological order: NA (Jan), JP (Jun), EU (Dec) ...
    expect(allTextContent).toBeVisible();
  });

  it('given releases with different granularities, normalizes dates correctly for comparison', () => {
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
        releasedAt: '2023-01-25T00:00:00Z', // Later in the month but...
        releasedAtGranularity: 'month', // ...only month precision.
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    // ... both should show as January, with JP first due to month granularity normalization ...
    const cells = screen.getAllByRole('cell');
    const contentCell = cells[1]; // Second cell contains the releases.

    // ... JP should appear before NA because month granularity normalizes to start of month ...
    expect(contentCell.textContent).toMatch(/JP.*January 2023.*NA.*Jan 15, 2023/i);
  });

  it('given releases with year granularity, displays only the year', () => {
    // ARRANGE
    const release = createGameRelease({
      region: 'jp',
      releasedAt: '2023-06-15T00:00:00Z',
      releasedAtGranularity: 'year',
    });

    render(<GameReleaseDatesRow releases={[release]} />);

    // ASSERT
    expect(screen.getByText(/2023/)).toBeVisible();

    expect(screen.queryByText(/jun/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/15/)).not.toBeInTheDocument();
  });

  it('given the same normalized date but different granularities, sorts by specificity', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'na',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'year',
      }),
      createGameRelease({
        id: 2,
        region: 'jp',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'eu',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'month',
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    const cells = screen.getAllByRole('cell');
    const contentCell = cells[1];

    expect(contentCell.textContent).toMatch(/JP.*Jan 1, 2023.*EU.*January 2023.*NA.*2023/i);
  });

  it('worldwide and other regions are normalized during deduplication', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'worldwide',
        releasedAt: '2023-02-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 2,
        region: null,
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'other',
        releasedAt: '2023-03-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    expect(screen.getByText(/jan 1, 2023/i)).toBeVisible();

    expect(screen.queryByText(/feb 1, 2023/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/mar 1, 2023/i)).not.toBeInTheDocument();
  });

  it('given releases with null granularity, treats them as lowest priority in sorting', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        id: 1,
        region: 'na',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: null,
      }),
      createGameRelease({
        id: 2,
        region: 'jp',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: 'day',
      }),
      createGameRelease({
        id: 3,
        region: 'eu',
        releasedAt: '2023-01-01T00:00:00Z',
        releasedAtGranularity: null,
      }),
    ];

    render(<GameReleaseDatesRow releases={releases} />);

    // ASSERT
    const cells = screen.getAllByRole('cell');
    const contentCell = cells[1];

    expect(contentCell.textContent).toMatch(/JP.*Jan 1, 2023.*(?:NA|EU).*2023.*(?:NA|EU).*2023/i);
  });
});
