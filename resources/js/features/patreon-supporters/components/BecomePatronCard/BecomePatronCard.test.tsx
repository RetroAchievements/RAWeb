import { render, screen } from '@/test';

import { BecomePatronCard } from './BecomePatronCard';

describe('Component: BecomePatronCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<BecomePatronCard />, {
      pageProps: {
        config: {
          services: {
            patreon: {
              userId: '12345',
            },
          },
        } as any,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no patreon user id configured, does not render the card', () => {
    // ARRANGE
    render(<BecomePatronCard />, {
      pageProps: {
        config: {
          services: {
            patreon: {
              userId: null,
            },
          },
        } as any,
      },
    });

    // ASSERT
    expect(
      screen.queryByText(/thank you to all our amazing patreon supporters/i),
    ).not.toBeInTheDocument();
  });

  it('given there is a patreon user id configured, renders the card with all content', () => {
    // ARRANGE
    render(<BecomePatronCard />, {
      pageProps: {
        config: {
          services: {
            patreon: {
              userId: '12345',
            },
          },
        } as any,
      },
    });

    // ASSERT
    expect(screen.getByText(/thank you to all our amazing patreon supporters/i)).toBeVisible();
    expect(screen.getByText(/this is a passion project/i)).toBeVisible();
    expect(
      screen.getByText(/your contribution goes directly towards helping keep the servers alive/i),
    ).toBeVisible();
    expect(screen.getByRole('link', { name: /become a patron/i })).toBeVisible();
  });

  it('given there is a patreon user id configured, the become a patron link has the correct href', () => {
    // ARRANGE
    const patreonUserId = '12345';
    render(<BecomePatronCard />, {
      pageProps: {
        config: {
          services: {
            patreon: {
              userId: patreonUserId,
            },
          },
        } as any,
      },
    });

    // ACT
    const becomePatronLink = screen.getByRole('link', { name: /become a patron/i });

    // ASSERT
    expect(becomePatronLink).toHaveAttribute(
      'href',
      `https://www.patreon.com/bePatron?u=${patreonUserId}`,
    );
  });
});
