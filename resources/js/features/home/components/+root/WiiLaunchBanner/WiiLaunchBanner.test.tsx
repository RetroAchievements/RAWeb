import { render, screen } from '@/test';
import { createHomePageProps } from '@/test/factories';

import { WiiLaunchBanner } from './WiiLaunchBanner';

describe('Component: WiiLaunchBanner', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<WiiLaunchBanner />, {
      pageProps: createHomePageProps({ wiiSetCount: 183 }),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no wii game count, renders nothing', () => {
    // ARRANGE
    render(<WiiLaunchBanner />, {
      pageProps: createHomePageProps({ wiiSetCount: null }),
    });

    // ASSERT
    expect(screen.queryByText(/wii/i)).not.toBeInTheDocument();
  });

  it('given there is a wii game count of zero, renders nothing', () => {
    // ARRANGE
    render(<WiiLaunchBanner />, {
      pageProps: createHomePageProps({ wiiSetCount: 0 }),
    });

    // ASSERT
    expect(screen.queryByText(/wii/i)).not.toBeInTheDocument();
  });

  it('displays the game count in the banner text', () => {
    // ARRANGE
    render(<WiiLaunchBanner />, {
      pageProps: createHomePageProps({ wiiSetCount: 183 }),
    });

    // ASSERT
    expect(screen.getByText(/explore all 183 achievement sets/i)).toBeVisible();
  });

  it('has an accessible link to the Wii system games page', () => {
    // ARRANGE
    render(<WiiLaunchBanner />, {
      pageProps: createHomePageProps({ wiiSetCount: 183 }),
    });

    // ASSERT
    const linkEl = screen.getByRole('link');

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('system.game.index'));
  });
});
