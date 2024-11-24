import { render, screen } from '@/test';
import { createHomePageProps, createZiggyProps } from '@/test/factories';

import { CurrentlyOnline } from './CurrentlyOnline';

// recharts is going to throw errors in JSDOM that we don't care about.
console.warn = vi.fn();

describe('Component: CurrentlyOnline', () => {
  const logEntries = [
    2487, 2335, 2193, 1963, 1869, 1765, 1676, 1531, 1538, 1583, 1555, 1579, 1636, 1807, 1881, 2007,
    2097, 2222, 2437, 2458, 2534, 2536, 2679, 2731, 2838, 2803, 2862, 2913, 2998, 3037, 3041, 3031,
    3063, 3084, 2996, 2956, 2914, 2845, 2945, 2882, 2800, 3423, 2666, 2508, 2331, 2177, 2022, 1873,
  ];

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /currently online/i })).toBeVisible();
  });

  it('given only one user is online, shows a singular user count message', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: {
        ziggy: createZiggyProps(),
        ...createHomePageProps({
          currentlyOnline: {
            logEntries,
            allTimeHighDate: null,
            allTimeHighPlayers: 1000,
            numCurrentPlayers: 1,
          },
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/user is currently online/i)).toBeVisible();
  });

  it('given many users are online, shows a plural user count message', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: {
        ziggy: createZiggyProps({ device: 'mobile' }),
        ...createHomePageProps({
          currentlyOnline: {
            logEntries,
            allTimeHighDate: null,
            allTimeHighPlayers: 1000,
            numCurrentPlayers: 100,
          },
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/users are currently online/i)).toBeVisible();
  });

  it('displays all-time high information', () => {
    // ARRANGE
    const allTimeHighDate = new Date('2024-08-07').toISOString();

    render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: {
        ziggy: createZiggyProps(),
        ...createHomePageProps({
          currentlyOnline: {
            logEntries,
            allTimeHighDate,
            allTimeHighPlayers: 4744,
            numCurrentPlayers: 100,
          },
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/all-time high/i)).toBeVisible();
    expect(screen.getByText(/4,744/i)).toBeVisible();
    expect(screen.getByText(/aug 7, 2024/i)).toBeVisible();

    expect(screen.getByText(/users are currently online/i)).toBeVisible();
  });

  it('does not crash if the all-time high date is missing', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<CurrentlyOnline />, {
      pageProps: {
        ziggy: createZiggyProps(),
        ...createHomePageProps({
          currentlyOnline: {
            logEntries,
            allTimeHighDate: null, // !!
            allTimeHighPlayers: 4744,
            numCurrentPlayers: 100,
          },
        }),
      },
    });

    // ASSERT
    expect(screen.queryByText(/all-time high/i)).not.toBeInTheDocument();
  });
});
