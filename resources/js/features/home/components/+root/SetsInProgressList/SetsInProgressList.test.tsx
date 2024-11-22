import { render, screen } from '@/test';
import { createAchievementSetClaim, createHomePageProps } from '@/test/factories';

import { SetsInProgressList } from './SetsInProgressList';

describe('Component: SetsInProgressList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<SetsInProgressList />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<SetsInProgressList />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /latest sets in progress/i })).toBeVisible();
  });

  it('given there are no completed claims, displays an empty state and does not crash', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<SetsInProgressList />);

    // ASSERT
    expect(screen.getByText(/couldn't find any sets in progress/i)).toBeVisible();
  });

  it('displays accessible table headers', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<SetsInProgressList />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /game/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /dev/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /type/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /started/i })).toBeVisible();
  });

  it('displays multiple table rows', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<SetsInProgressList />, {
      pageProps: createHomePageProps({
        newClaims: [
          createAchievementSetClaim(),
          createAchievementSetClaim(),
          createAchievementSetClaim(),
          createAchievementSetClaim(),
          createAchievementSetClaim(),
        ],
      }),
    });

    // ASSERT
    const rowEls = screen.getAllByRole('row');

    expect(rowEls.length).toEqual(6); // 5, plus the header row.
  });

  it('displays a link to the "See More" page', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<SetsInProgressList />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /see more/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'claims.active');
  });
});
