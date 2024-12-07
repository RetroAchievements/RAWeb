import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { RandomGameButton } from './RandomGameButton';

// Suppress "Error: AggregateError".
console.error = vi.fn();

const mockWindowOpen = vi.fn();
const mockLocationAssign = vi.fn();

describe('Component: RandomGameButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    Object.defineProperty(window, 'open', {
      value: mockWindowOpen,
      writable: true,
    });

    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <RandomGameButton
        columnFilters={[]}
        variant="toolbar"
        apiRouteName="api.game.random"
        disabled={false}
      />,
      { pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is on desktop, navigates them on click in a new tab', async () => {
    // ARRANGE
    vi.spyOn(axios, 'get').mockResolvedValue({ data: { gameId: 12345 } });

    render(
      <RandomGameButton
        columnFilters={[]}
        variant="toolbar"
        apiRouteName="api.game.random"
        disabled={false}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /surprise me/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockWindowOpen).toHaveBeenCalledWith(['game.show', 12345], '_blank');
    });
  });

  it('given the user is on mobile, navigates them on click in the same window', async () => {
    // ARRANGE
    vi.spyOn(axios, 'get').mockResolvedValue({ data: { gameId: 12345 } });

    render(
      <RandomGameButton
        columnFilters={[]}
        variant="mobile-drawer"
        apiRouteName="api.game.random"
        disabled={false}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /surprise me/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockLocationAssign).toHaveBeenCalledWith(['game.show', 12345]);
    });
  });

  it('given the disabled prop is truthy, disables the button', () => {
    // ARRANGE
    render(
      <RandomGameButton
        columnFilters={[]}
        variant="toolbar"
        apiRouteName="api.game.random"
        disabled={true}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /surprise me/i })).toBeDisabled();
  });
});
