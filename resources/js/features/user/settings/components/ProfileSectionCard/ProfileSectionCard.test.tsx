import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { ProfileSectionCard } from './ProfileSectionCard';

describe('Component: ProfileSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        can: {},
        userSettings: createUser(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has no visible role, tells them', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        can: {},
        userSettings: createUser({ visibleRole: null }),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/visible role/i)).toHaveTextContent(/none/i);
  });

  it('given the user has a visible role, tells them', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        can: {},
        userSettings: createUser({ visibleRole: 'Some Role' }),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/visible role/i)).toHaveTextContent(/some role/i);
  });

  it('given the user is unable to change their motto, tells them', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        can: {
          updateMotto: false,
        },
        userSettings: createUser({ visibleRole: 'Some Role' }),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/motto/i)).toBeDisabled();
    expect(screen.getByText(/verify your email to update your motto/i)).toBeVisible();
  });

  it('given the user tries to delete all comments from their wall, makes a call to the server with the request', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ username: 'Scott', id: 1 }),
        },
        can: {},
        userSettings: createUser({ visibleRole: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete all comments on/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledTimes(1);
  });

  it("correctly prepopulates the user's motto and wall preference", () => {
    // ARRANGE
    const mockMotto = 'my motto';
    const mockUserWallActive = true;

    render(<ProfileSectionCard />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ username: 'Scott' }),
        },
        can: {},
        userSettings: createUser({
          visibleRole: null,
          motto: mockMotto,
          userWallActive: mockUserWallActive,
        }),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/motto/i)).toHaveValue(mockMotto);
    expect(screen.getByLabelText(/allow comments/i)).toBeChecked();
  });

  it('given the user tries to submit new profile settings, makes a call to the server with the request', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    const mockMotto = 'my motto';
    const mockUserWallActive = true;

    render(<ProfileSectionCard />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ username: 'Scott' }),
        },
        can: {
          updateMotto: true,
        },
        userSettings: createUser({
          visibleRole: null,
          motto: mockMotto,
          userWallActive: mockUserWallActive,
        }),
      },
    });

    // ACT
    const mottoField = screen.getByLabelText(/motto/i);
    const userWallActiveField = screen.getByLabelText(/allow comments/i);

    await userEvent.clear(mottoField);
    await userEvent.type(mottoField, 'https://www.youtube.com/watch?v=YYOKMUTTDdA');
    await userEvent.click(userWallActiveField);

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('settings.profile.update'), {
      motto: 'https://www.youtube.com/watch?v=YYOKMUTTDdA',
      userWallActive: false,
    });
  });
});
