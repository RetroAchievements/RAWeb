import { render, screen } from '@/test';
import { createAchievementSetClaim, createHomePageProps } from '@/test/factories';

import { NewSetsList } from './NewSetsList';

describe('Component: NewSetsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<NewSetsList />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<NewSetsList />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /just released/i })).toBeVisible();
  });

  it('given there are no completed claims, displays an empty state and does not crash', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<NewSetsList />);

    // ASSERT
    expect(screen.getByText(/couldn't find any completed claims/i)).toBeVisible();
  });

  it('displays accessible table headers', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<NewSetsList />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /game/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /dev/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /type/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /finished/i })).toBeVisible();
  });

  it('displays multiple table rows', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<NewSetsList />, {
      pageProps: createHomePageProps({
        completedClaims: [
          createAchievementSetClaim(),
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

    expect(rowEls.length).toEqual(7); // 6, plus the header row.
  });

  it('displays a link to the "See More" page', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<NewSetsList />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /see more/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'claims.completed');
  });
});
