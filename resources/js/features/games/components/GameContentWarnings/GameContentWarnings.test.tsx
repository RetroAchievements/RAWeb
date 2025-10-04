import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { GameContentWarnings } from './GameContentWarnings';

describe('Component: GameContentWarnings', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameContentWarnings />, {
      pageProps: {
        hasMatureContent: false,
        hubs: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the mature content flag is not set and no photosensitive warning hub attached, renders nothing', () => {
    // ARRANGE
    render(<GameContentWarnings />, {
      pageProps: {
        hasMatureContent: false,
        hubs: [],
      },
    });

    // ASSERT
    expect(screen.queryByTestId('content-warnings')).not.toBeInTheDocument();
  });

  it('given the mature content flag is set, displays the mature content warning', () => {
    // ARRANGE
    render(<GameContentWarnings />, {
      pageProps: {
        hasMatureContent: true,
        hubs: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/mature content/i)).toBeVisible();
  });

  it('given the photosensitive warning hub is attached, displays the photosensitive warning', () => {
    // ARRANGE
    const photosensitiveHub = createGameSet({
      id: 25577,
      title: 'Photosensitive Warning',
    });

    render(<GameContentWarnings />, {
      pageProps: {
        hasMatureContent: false,
        hubs: [photosensitiveHub],
      },
    });

    // ASSERT
    expect(screen.getByText(/photosensitive epilepsy warning/i)).toBeVisible();
  });

  it('given both the mature content flag is set and the photosensitive warning hub is attached, displays both warnings', () => {
    // ARRANGE
    const photosensitiveHub = createGameSet({
      id: 25577,
      title: 'Photosensitive Warning',
    });

    render(<GameContentWarnings />, {
      pageProps: {
        hasMatureContent: true,
        hubs: [photosensitiveHub],
      },
    });

    // ASSERT
    expect(screen.getByText(/mature content/i)).toBeVisible();
    expect(screen.getByText(/photosensitive epilepsy warning/i)).toBeVisible();
  });
});
