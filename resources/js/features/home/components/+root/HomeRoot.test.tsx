import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createHomePageProps, createZiggyProps } from '@/test/factories';

import { HomeRoot } from './HomeRoot';

// recharts is going to throw errors in JSDOM that we don't care about.
console.warn = vi.fn();

describe('Component: HomeRoot', () => {
  beforeEach(() => {
    mockAllIsIntersecting(false);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: { ...createHomePageProps(), ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays several section components', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: { ...createHomePageProps(), ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /news/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /just released/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /active players/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /currently online/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /latest sets in progress/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /recent forum posts/i })).toBeVisible();
  });

  it('given the user is not logged in, shows a welcome section', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: { ...createHomePageProps(), auth: null, ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /welcome/i })).toBeVisible();
  });

  it('given the user is logged in, does not show a welcome section', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: {
        ...createHomePageProps(),
        auth: { user: createAuthenticatedUser() },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /welcome/i })).not.toBeInTheDocument();
  });

  it('given the user is new, shows a new user call-to-action', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: {
        ...createHomePageProps(),
        auth: { user: createAuthenticatedUser({ isNew: true }) },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /getting started/i })).toBeVisible();
  });

  it('given the user is not new, does not show a new user call-to-action', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<HomeRoot />, {
      pageProps: {
        ...createHomePageProps(),
        auth: { user: createAuthenticatedUser({ isNew: false }) },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /getting started/i })).not.toBeInTheDocument();
  });
});
