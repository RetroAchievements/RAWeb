import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createGameSet, createPageBanner, createRaEvent } from '@/test/factories';

import { EventDesktopBanner } from './EventDesktopBanner';

describe('Component: EventDesktopBanner', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        can: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the event title', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          legacyGame: createGame({ title: 'Achievement of the Week 2025' }),
        }),
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
      'Achievement of the Week 2025',
    );
  });

  it('displays the event badge image', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          legacyGame: createGame({
            title: 'Test Event',
            badgeUrl: 'https://example.com/badge.png',
          }),
        }),
        can: {},
      },
    });

    // ASSERT
    const badge = screen.getByAltText('Test Event');
    expect(badge).toHaveAttribute('src', 'https://example.com/badge.png');
  });

  it('given the event belongs to the Community Events hub, displays "Community Events"', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        breadcrumbs: [createGameSet({ id: 1 }), createGameSet({ id: 4 })],
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByText('Community Events')).toBeVisible();
  });

  it('given the event belongs to the Developer Events hub, displays "Developer Events"', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        breadcrumbs: [createGameSet({ id: 1 }), createGameSet({ id: 5 })],
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByText('Developer Events')).toBeVisible();
  });

  it('renders the blurred backdrop from the fallback banner', () => {
    // ARRANGE
    const banner = createPageBanner({
      desktopMdWebp: '/assets/images/banner/fallback-desktop-md.webp',
      isFallback: true,
    });

    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        banner,
        can: {},
      },
    });

    // ASSERT
    const blurredBackdrop = screen.getByTestId('blurred-backdrop');
    expect(blurredBackdrop).toHaveAttribute(
      'src',
      '/assets/images/banner/fallback-desktop-md.webp',
    );
  });

  it('given a long title, applies a smaller font size class', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          legacyGame: createGame({ title: 'Challenge League 2024: Ticket to the Universe' }),
        }),
        can: {},
      },
    });

    // ASSERT
    const heading = screen.getByRole('heading', { level: 1 });
    expect(heading).toHaveClass('!text-base');
  });

  it('given a very long title, clamps it to two lines', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          legacyGame: createGame({
            title: 'The Very Long Challenge League 2024: An Extremely Extended Subtitle',
          }),
        }),
        can: {},
      },
    });

    // ASSERT
    const heading = screen.getByRole('heading', { level: 1 });
    expect(heading).toHaveClass('line-clamp-2');
  });

  it('always uses the fixed compact height', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        can: {},
      },
    });

    // ASSERT
    const bannerEl = screen.getByTestId('desktop-banner');
    expect(bannerEl).toHaveClass('lg:h-[212px]');
  });

  it('given the user can manage events, shows the manage chip', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({ id: 15 }),
        can: { manageEvents: true },
      },
    });

    // ASSERT
    const manageLink = screen.getByRole('link', { name: /manage/i });
    expect(manageLink).toBeVisible();
    expect(manageLink).toHaveAttribute('href', '/manage/events/15');
  });

  it('given the user can manage events and hovers the manage chip, reveals the label text', async () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({ id: 15 }),
        can: { manageEvents: true },
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /manage/i }));

    // ASSERT
    expect(screen.getByText('Manage')).toBeVisible();
  });

  it('given the user can manage events and unhovers the manage chip, begins hiding the label text', async () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({ id: 15 }),
        can: { manageEvents: true },
      },
    });

    const manageLink = screen.getByRole('link', { name: /manage/i });

    // ACT
    await userEvent.hover(manageLink);
    await userEvent.unhover(manageLink);

    // ASSERT
    const manageText = screen.getByText('Manage');
    expect(Number(manageText.style.opacity)).toBeLessThan(1);
  });

  it('given the user cannot manage events, does not show the manage chip', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent(),
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });

  it('given a concluded event with both dates, shows a single date range chip', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          state: 'concluded',
          activeFrom: '2025-01-06T00:00:00Z',
          activeThrough: '2026-01-04T00:00:00Z',
        }),
        can: {},
      },
    });

    // ASSERT
    expect(screen.queryByText(/started/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/ended/i)).not.toBeInTheDocument();

    // The date range chip should contain both dates.
    expect(screen.getByText(/jan 6, 2025/i)).toBeVisible();
    expect(screen.getByText(/jan 4, 2026/i)).toBeVisible();
  });

  it('given an active event, shows separate start and end date chips', () => {
    // ARRANGE
    render(<EventDesktopBanner />, {
      pageProps: {
        event: createRaEvent({
          state: 'active',
          activeFrom: '2025-01-06T00:00:00Z',
          activeThrough: '2027-01-04T00:00:00Z',
        }),
        can: {},
      },
    });

    // ASSERT
    expect(screen.getByText(/started jan 6, 2025/i)).toBeVisible();
    expect(screen.getByText(/ends jan 4, 2027/i)).toBeVisible();
  });
});
