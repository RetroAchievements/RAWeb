import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { render, screen } from '@/test';
import { createHomePageProps } from '@/test/factories';

import { ActivePlayers } from './ActivePlayers';

describe('Component: ActivePlayers', () => {
  beforeEach(() => {
    mockAllIsIntersecting(false);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<ActivePlayers />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<ActivePlayers />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /active players/i })).toBeVisible();
  });

  it('displays an active player feed', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<ActivePlayers />, {
      pageProps: createHomePageProps(),
    });

    // ASSERT
    // implementation details tested in common/components/ActivePlayerFeed
    expect(screen.getByTestId('active-player-feed'));
  });
});
