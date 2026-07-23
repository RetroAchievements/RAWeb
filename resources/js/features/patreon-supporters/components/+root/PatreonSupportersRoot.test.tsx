import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { PatreonSupportersRoot } from './PatreonSupportersRoot';

describe('Component: PatreonSupportersRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 0,
        tier2Count: 0,
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
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 0,
        tier2Count: 0,
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
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 0,
        tier2Count: 0,
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
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [recentSupporter1, recentSupporter2],
        tier1Count: 0,
        tier2Count: 0,
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
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 0,
        tier2Count: 0,
      },
    });

    // ASSERT
    expect(screen.queryByText(/our newest patreon supporters/i)).not.toBeInTheDocument();
  });

  it('given there are $2 supporters, renders them in their own section above the $1 supporters', () => {
    // ARRANGE
    const tier2Supporter = createUser({ displayName: 'BigSpender' });
    const tier1Supporter = createUser({ displayName: 'RegularSupporter' });

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [tier1Supporter],
        initialTier2Supporters: [tier2Supporter],
        recentSupporters: [],
        tier1Count: 1,
        tier2Count: 1,
      },
    });

    // ASSERT
    const tier2Heading = screen.getByText(/\$2 supporters \(1\)/i);
    const tier1Heading = screen.getByText(/\$1 supporters \(1\)/i);

    expect(tier2Heading).toBeVisible();
    expect(tier1Heading).toBeVisible();

    expect(screen.getByRole('link', { name: /bigspender/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /regularsupporter/i })).toBeVisible();
  });

  it('given there are no $2 supporters, does not render the $2 supporters section', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 0,
        tier2Count: 0,
      },
    });

    // ASSERT
    expect(screen.queryByText(/\$2 supporters/i)).not.toBeInTheDocument();
    expect(screen.getByText(/\$1 supporters \(0\)/i)).toBeVisible();
  });

  it('given deferred supporters have loaded for both tiers, renders the initial and deferred supporters', () => {
    // ARRANGE
    const initialTier2Supporter = createUser({ displayName: 'InitialBigSpender' });
    const deferredTier2Supporter = createUser({ displayName: 'DeferredBigSpender' });
    const initialTier1Supporter = createUser({ displayName: 'InitialSupporter' });
    const deferredTier1Supporter = createUser({ displayName: 'DeferredSupporter' });

    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: [deferredTier1Supporter],
        deferredTier2Supporters: [deferredTier2Supporter],
        initialTier1Supporters: [initialTier1Supporter],
        initialTier2Supporters: [initialTier2Supporter],
        recentSupporters: [],
        tier1Count: 2,
        tier2Count: 2,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /initialbigspender/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /deferredbigspender/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /initialsupporter/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /deferredsupporter/i })).toBeVisible();
  });

  it('given both tiers have supporters still loading, displays a loading state for each tier', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [createUser()],
        initialTier2Supporters: [createUser()],
        recentSupporters: [],
        tier1Count: 10, // !! more than the 1 we're showing
        tier2Count: 10, // !! more than the 1 we're showing
      },
    });

    // ASSERT
    expect(screen.getAllByText(/loading/i)).toHaveLength(2);
  });

  it('given all supporters have loaded, does not display loading text', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: [createUser()],
        deferredTier2Supporters: [createUser()],
        initialTier1Supporters: [createUser()],
        initialTier2Supporters: [createUser()],
        recentSupporters: [],
        tier1Count: 2,
        tier2Count: 2,
      },
    });

    // ASSERT
    expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
  });

  it('displays the correct counts in the tier headings', () => {
    // ARRANGE
    render(<PatreonSupportersRoot />, {
      pageProps: {
        config: { services: { patreon: { userId: null } } } as any,
        deferredTier1Supporters: null,
        deferredTier2Supporters: null,
        initialTier1Supporters: [],
        initialTier2Supporters: [],
        recentSupporters: [],
        tier1Count: 1419,
        tier2Count: 120,
      },
    });

    // ASSERT
    expect(screen.getByText(/\$1 supporters \(1,419\)/i)).toBeVisible();
    expect(screen.getByText(/\$2 supporters \(120\)/i)).toBeVisible();
  });
});
