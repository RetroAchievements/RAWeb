import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { HubHeading } from './HubHeading';

describe('Component: HubHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub: createGameSet({ type: 'hub' }) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a hub with a badge URL, displays the badge image', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', badgeUrl: 'https://example.com/badge.png' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub },
    });

    // ASSERT
    const imgEl = screen.getByRole('img', { name: hub.title! });

    expect(imgEl).toBeVisible();
    expect(imgEl).toHaveAttribute('src', hub.badgeUrl);
  });

  it('given a hub without a badge URL, does not display a badge image', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', badgeUrl: null });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: hub.title! })).not.toBeInTheDocument();
  });

  it('given a hub title, displays the cleaned hub title', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', title: '[Series - Sonic the Hedgehog]' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
      /series - sonic the hedgehog/i,
    );

    expect(screen.queryByText(/\[/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/\]/i)).not.toBeInTheDocument();
  });
});
