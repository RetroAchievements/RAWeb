import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { ProfileTabPanel } from './ProfileTabPanel';

describe('Component: ProfileTabPanel', () => {
  it('renders the profile cards and relocated sections for a standard user', () => {
    // ARRANGE
    render(<ProfileTabPanel />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            isMuted: false,
            preferencesBitfield: 139687,
          }),
        },
        can: { updateAvatar: true, updateMotto: true },
        userSettings: createUser(),
      },
    });

    // ACT
    const headings = screen.getAllByRole('heading');

    // ASSERT
    expect(headings.map((heading) => heading.textContent)).toEqual([
      'Profile',
      'Avatar',
      'Preferences',
      'Locale',
    ]);
  });

  it('keeps the avatar hidden for a muted user while retaining the other profile sections', () => {
    // ARRANGE
    render(<ProfileTabPanel />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            isMuted: true,
            preferencesBitfield: 139687,
          }),
        },
        can: { updateAvatar: true, updateMotto: true },
        userSettings: createUser(),
      },
    });

    // ACT
    const avatarHeading = screen.queryByRole('heading', { name: 'Avatar' });

    // ASSERT
    expect(avatarHeading).not.toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Profile' })).toBeVisible();
    expect(screen.getByRole('heading', { name: 'Preferences' })).toBeVisible();
    expect(screen.getByRole('heading', { name: 'Locale' })).toBeVisible();
  });
});
