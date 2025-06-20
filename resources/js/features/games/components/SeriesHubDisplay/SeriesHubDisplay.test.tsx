import { render, screen } from '@/test';
import { createGameSet, createSeriesHub } from '@/test/factories';

import { SeriesHubDisplay } from './SeriesHubDisplay';

describe('Component: SeriesHubDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        badgeUrl: 'https://example.com/badge.png',
        title: '[Series - Super Mario]',
      }),
      totalGameCount: 5,
      achievementsPublished: 100,
      pointsTotal: 1000,
    });

    const { container } = render(<SeriesHubDisplay seriesHub={seriesHub} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a series hub, displays the title and associated metadata', () => {
    // ARRANGE
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        badgeUrl: 'https://example.com/badge.png',
        title: '[Series - Super Mario]',
      }),
      totalGameCount: 15,
      achievementsPublished: 500,
      pointsTotal: 5000,
    });

    render(<SeriesHubDisplay seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByText(/series/i)).toBeVisible();
    expect(screen.getByText(/super mario/i)).toBeVisible();
    expect(screen.getByText('15')).toBeVisible();
    expect(screen.getByText('games')).toBeVisible();
    expect(screen.getByText('500')).toBeVisible();
    expect(screen.getByText('achievements')).toBeVisible();
    expect(screen.getByText('5,000')).toBeVisible();
    expect(screen.getByText('points')).toBeVisible();
  });

  it('given a hub with a prefixed title, cleans it properly', () => {
    // ARRANGE
    const seriesHub = createSeriesHub({
      hub: createGameSet({
        title: '[Series - Super Mario]',
      }),
    });

    render(<SeriesHubDisplay seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.queryByText(/\[series/i)).not.toBeInTheDocument();
    expect(screen.getByText(/super mario/i)).toBeVisible();
  });

  it('given not all games in the series have achievements, shows the count with achievements', () => {
    // ARRANGE
    const seriesHub = createSeriesHub({
      hub: createGameSet(),
      totalGameCount: 10,
      gamesWithAchievementsCount: 7, // !!
    });

    render(<SeriesHubDisplay seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.getByText('(7 with achievements)')).toBeVisible();
  });

  it('given all games in the series have achievements, does not show the redundant label', () => {
    // ARRANGE
    const seriesHub = createSeriesHub({
      hub: createGameSet(),
      totalGameCount: 10,
      gamesWithAchievementsCount: 10, // !!
    });

    render(<SeriesHubDisplay seriesHub={seriesHub} />);

    // ASSERT
    expect(screen.queryByText(/with achievements/i)).not.toBeInTheDocument();
  });
});
