import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createUser } from '@/test/factories';

import { settingsTabAtom } from '../../state/settings.atoms';
import { SettingsRoot } from './SettingsRoot';

describe('Component: SettingsRoot', () => {
  const originalLocation = window.location;

  beforeEach(() => {
    vi.spyOn(router, 'visit').mockImplementation(() => {});

    delete (window as any).location;
    (window.location as any) = {
      ...originalLocation,
      href: 'https://retroachievements.org/settings',
      pathname: '/settings',
      search: '',
    } as Location;
  });

  afterEach(() => {
    (window as any).location = originalLocation;
    vi.restoreAllMocks();
  });

  it('renders all four tabs with the profile panel active by default', () => {
    // ARRANGE
    render(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { updateMotto: true },
        userSettings: createUser(),
      },
    });

    // ACT
    const profileTab = screen.getAllByRole('tab', { name: 'Profile' })[0];

    // ASSERT
    expect(screen.getAllByRole('tab')).toHaveLength(8);
    expect(profileTab).toHaveAttribute('aria-selected', 'true');
  });

  it('activates the tab selected by the hydrated atom', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/settings?tab=account';
    window.location.search = '?tab=account';

    render(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { updateMotto: true },
        userSettings: createUser(),
      },
      jotaiAtoms: [
        [settingsTabAtom, 'account'],
        //
      ],
    });

    // ACT
    const accountTab = screen.getAllByRole('tab', { name: 'Account' })[0];

    // ASSERT
    expect(accountTab).toHaveAttribute('aria-selected', 'true');
  });

  it('switches panels, pushes the tab into history, and focuses the active panel heading', async () => {
    // ARRANGE
    render(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { updateMotto: true },
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getAllByRole('tab', { name: 'Notifications' })[0]);

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      expect.stringContaining('?tab=notifications'),
      expect.objectContaining({ preserveScroll: true, preserveState: true }),
    );
    await waitFor(() => {
      expect(document.activeElement).toHaveTextContent('Notifications');
    });
  });

  it('removes the tab query parameter when returning to the profile tab', async () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/settings?tab=account';
    window.location.search = '?tab=account';

    render(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { updateMotto: true },
        userSettings: createUser(),
      },
      jotaiAtoms: [[settingsTabAtom, 'account']],
    });

    // ACT
    await userEvent.click(screen.getAllByRole('tab', { name: 'Profile' })[0]);

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      expect.not.stringContaining('tab=profile'),
      expect.objectContaining({ preserveScroll: true, preserveState: true }),
    );
  });
});
