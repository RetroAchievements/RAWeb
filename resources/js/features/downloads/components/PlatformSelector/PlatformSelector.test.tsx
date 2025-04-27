import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createPlatform } from '@/test/factories';

import { selectedPlatformIdAtom } from '../../state/downloads.atoms';
import { PlatformSelector } from './PlatformSelector';

describe('Component: PlatformSelector', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: [],
        userDetectedPlatformId: null,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a list of platforms, shows them in order by orderColumn', () => {
    // ARRANGE
    const platforms = [
      createPlatform({ id: 1, name: 'Platform C', orderColumn: 2 }),
      createPlatform({ id: 2, name: 'Platform A', orderColumn: 0 }),
      createPlatform({ id: 3, name: 'Platform B', orderColumn: 1 }),
      createPlatform({ id: 4, name: 'Hidden Platform', orderColumn: -1 }), // !!
    ];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: null,
      },
    });

    // ASSERT
    const visiblePlatformButtons = screen.getAllByRole('button');
    expect(visiblePlatformButtons).toHaveLength(4); // +1 to account for the "All Platforms" button

    const platformNames = visiblePlatformButtons.map((button) => button.textContent);
    expect(platformNames).toEqual(['All Platforms', 'Platform A', 'Platform B', 'Platform C']);
  });

  it('given a detected platform, shows a success message', () => {
    // ARRANGE
    const platforms = [createPlatform({ id: 1, name: 'Windows', orderColumn: 0 })];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: 1,
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, 1],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/windows detected/i)).toBeVisible();
  });

  it('given the user selects a platform, updates the selected platform state', async () => {
    // ARRANGE
    const platforms = [
      createPlatform({ id: 1, name: 'Windows', orderColumn: 0 }),
      createPlatform({ id: 2, name: 'Linux', orderColumn: 1 }),
    ];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: null,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /linux/i }));

    // ASSERT
    const linuxButton = screen.getByRole('button', { name: /linux/i });
    expect(linuxButton.getAttribute('aria-pressed')).toEqual('true');
  });

  it('given the user selects a platform after auto-detection, hides the detection message', async () => {
    // ARRANGE
    const platforms = [
      createPlatform({ id: 1, name: 'Windows', orderColumn: 0 }),
      createPlatform({ id: 2, name: 'Linux', orderColumn: 1 }),
    ];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: 1,
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, 1],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /linux/i }));

    // ASSERT
    expect(screen.queryByText(/detected/i)).not.toBeInTheDocument();
  });

  it('given the user clicks All Platforms, clears the platform selection', async () => {
    // ARRANGE
    const platforms = [createPlatform({ id: 1, name: 'Windows', orderColumn: 0 })];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: 1,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /all platforms/i }));

    // ASSERT
    const allPlatformsButton = screen.getByRole('button', { name: /all platforms/i });
    expect(allPlatformsButton.getAttribute('aria-pressed')).toEqual('true');
  });

  it('given a platform has no execution environment, renders without an environment icon', () => {
    // ARRANGE
    const platforms = [
      createPlatform({
        id: 1,
        name: 'Windows',
        orderColumn: 0,
        executionEnvironment: null,
      }),
    ];

    const { container } = render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: 1,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getAllByRole('button', { name: /^windows$/i })[0]).toBeVisible();
  });

  it('given a detected platform on mobile, selecting it updates the platform state', async () => {
    // ARRANGE
    const platforms = [
      createPlatform({
        id: 1,
        name: 'Windows',
        orderColumn: 0,
        executionEnvironment: 'native' as any,
      }),
    ];

    render(<PlatformSelector />, {
      pageProps: {
        allPlatforms: platforms,
        userDetectedPlatformId: 1,
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('button', { name: /all platforms/i })[0]);

    const mobileDetectedButton = screen.getAllByRole('button', { name: /windows/i })[0];
    await userEvent.click(mobileDetectedButton);

    // ASSERT
    expect(mobileDetectedButton.getAttribute('aria-pressed')).toEqual('true');
  });
});
