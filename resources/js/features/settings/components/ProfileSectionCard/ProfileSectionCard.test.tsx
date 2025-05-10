import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { ProfileSectionCard } from './ProfileSectionCard';

// Suppress setState() warnings that only happen in JSDOM.
console.error = vi.fn();

describe('Component: ProfileSectionCard', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

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
        userSettings: createUser(),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/visible role/i)).toHaveTextContent(/none/i);
  });

  it('given the user has a visible role, tells them', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        displayableRoles: [{ id: 6, name: 'developer' }],
        auth: {
          user: createAuthenticatedUser({
            visibleRole: { id: 6, name: 'developer' },
          }),
        },
        can: {},
        userSettings: createUser(),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/visible role/i)).toHaveTextContent(/developer/i);
  });

  it('given the user is unable to change their motto, tells them', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        can: {
          updateMotto: false,
        },
        userSettings: createUser(),
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
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete all comments on/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledOnce();
  });

  it('given the user clicks the delete all comments button but does not confirm, does not make a call to the server with the request', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);

    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ username: 'Scott', id: 1 }),
        },
        can: {},
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete all comments on/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
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
        displayableRoles: [], // !! no selectable roles
        auth: {
          user: createAuthenticatedUser({
            username: 'Scott',
          }),
        },
        can: {
          updateMotto: true,
        },
        userSettings: createUser({
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
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.profile.update'), {
      motto: 'https://www.youtube.com/watch?v=YYOKMUTTDdA',
      userWallActive: false,
    });
  });

  it('given the user has selectable visible roles, properly submits their selection', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    const mockMotto = 'my motto';
    const mockUserWallActive = true;

    const displayableRoles: App.Data.Role[] = [
      { id: 2, name: 'administrator' },
      { id: 6, name: 'developer' },
    ];

    const visibleRole = displayableRoles[1];

    render(<ProfileSectionCard />, {
      pageProps: {
        displayableRoles, // !! two of them
        auth: {
          user: createAuthenticatedUser({
            visibleRole, // !! developer
            username: 'Scott',
          }),
        },
        can: {
          updateMotto: true,
        },
        userSettings: createUser({
          motto: mockMotto,
          userWallActive: mockUserWallActive,
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /role/i }));
    await userEvent.click(screen.getByRole('option', { name: /admin/i }));

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.profile.update'), {
      motto: 'my motto',
      userWallActive: true,
      visibleRoleId: 2,
    });
  });

  it('given multiple visible roles are available, displays them sorted alphabetically by translated name', async () => {
    // ARRANGE
    const displayableRoles: App.Data.Role[] = [
      { id: 1, name: 'zdev' },
      { id: 2, name: 'adev' },
      { id: 3, name: 'mdev' },
    ];

    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        displayableRoles,
        auth: {
          user: createAuthenticatedUser({
            visibleRole: displayableRoles[0],
          }),
        },
        can: {
          updateMotto: true,
        },
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /role/i }));

    // ASSERT
    const optionEls = screen.getAllByRole('option');

    expect(optionEls[0]).toHaveTextContent(/adev/i);
    expect(optionEls[1]).toHaveTextContent(/mdev/i);
    expect(optionEls[2]).toHaveTextContent(/zdev/i);
  });

  it('given the user has a single visible role, still allows them to submit the form', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    const displayableRoles: App.Data.Role[] = [{ id: 1, name: 'zdev' }];

    render<App.Community.Data.UserSettingsPageProps>(<ProfileSectionCard />, {
      pageProps: {
        displayableRoles,
        auth: {
          user: createAuthenticatedUser({
            visibleRole: displayableRoles[0],
          }),
        },
        can: {
          updateMotto: true,
        },
        userSettings: createUser({ userWallActive: false }),
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
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.profile.update'), {
      motto: 'https://www.youtube.com/watch?v=YYOKMUTTDdA',
      userWallActive: true,
      visibleRoleId: 1,
    });
  });
});
