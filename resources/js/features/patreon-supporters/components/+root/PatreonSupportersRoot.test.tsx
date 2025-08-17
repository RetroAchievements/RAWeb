import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { PatreonSupportersRoot } from './PatreonSupportersRoot';

describe('Component: PatreonSupportersRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [],
        totalCount: 0,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is a patreon user id configured, renders the become patron card', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: '12345' } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [],
        totalCount: 0,
      },
    });

    // ASSERT
    expect(screen.getByText(/thank you to all our amazing patreon supporters/i)).toBeVisible();
  });

  it('given there is no patreon user id configured, does not render the become patron card', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [],
        totalCount: 0,
      },
    });

    // ASSERT
    expect(
      screen.queryByText(/thank you to all our amazing patreon supporters/i),
    ).not.toBeInTheDocument();
  });

  it('given there are recent supporters, renders the recent supporters section', () => {
    // ARRANGE
    const recentSupporter1 = createUser({ displayName: 'RecentUser1' });
    const recentSupporter2 = createUser({ displayName: 'RecentUser2' });

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [recentSupporter1, recentSupporter2],
        totalCount: 2,
      },
    });

    // ASSERT
    expect(screen.getByText(/our newest patreon supporters/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /recentuser1/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /recentuser2/i })).toBeVisible();
  });

  it('given there are no recent supporters, does not render the recent supporters section', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [],
        totalCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByText(/our newest patreon supporters/i)).not.toBeInTheDocument();
  });

  it('given there are initial supporters, renders them in the all supporters section', () => {
    // ARRANGE
    const supporter1 = createUser({ displayName: 'Supporter1' });
    const supporter2 = createUser({ displayName: 'Supporter2' });

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [supporter1, supporter2],
        recentSupporters: [],
        totalCount: 2,
      },
    });

    // ASSERT
    expect(screen.getByText(/all supporters \(2\)/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /supporter1/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /supporter2/i })).toBeVisible();
  });

  it('given deferred supporters have loaded, renders both initial and deferred supporters', () => {
    // ARRANGE
    const initialSupporter = createUser({ displayName: 'InitialSupporter' });
    const deferredSupporter = createUser({ displayName: 'DeferredSupporter' });

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: [deferredSupporter],
        initialSupporters: [initialSupporter],
        recentSupporters: [],
        totalCount: 2,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /initialsupporter/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /deferredsupporter/i })).toBeVisible();
  });

  it('given deferred supporters are still loading and there are more supporters than shown, displays loading text', () => {
    // ARRANGE
    const supporter = createUser();

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [supporter],
        recentSupporters: [],
        totalCount: 10, // !! more than the 1 we're showing
      },
    });

    // ASSERT
    expect(screen.getByText(/loading/i)).toBeVisible();
  });

  it('given all supporters have loaded, does not display loading text', () => {
    // ARRANGE
    const supporter1 = createUser();
    const supporter2 = createUser();

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: [supporter2],
        initialSupporters: [supporter1],
        recentSupporters: [],
        totalCount: 2,
      },
    });

    // ASSERT
    expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
  });

  it('displays the correct total count in the all supporters heading', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredSupporters: null,
        initialSupporters: [],
        recentSupporters: [],
        totalCount: 100,
      },
    });

    // ASSERT
    expect(screen.getByText(/all supporters \(100\)/i)).toBeVisible();
  });
});
