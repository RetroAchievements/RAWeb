import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { requestedUsernameAtom } from '../../state/settings.atoms';
import { ChangeUsernameSectionCard } from './ChangeUsernameSectionCard';

// Suppress setState() warnings that only happen in JSDOM.
console.error = vi.fn();

describe('Component: ChangeUsernameSectionCard', () => {
  const originalLocation = window.location;

  afterEach(() => {
    window.location = originalLocation;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has a pending username request, shows the pending request alert', () => {
    // ARRANGE
    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
      jotaiAtoms: [[requestedUsernameAtom, 'new-username']],
    });

    // ASSERT
    expect(screen.getByText(/your username change request is being reviewed/i)).toBeVisible();
    expect(screen.getByText(/new-username/i)).toBeVisible();
  });

  it('given the user cannot create a username change request, shows the wait alert', () => {
    // ARRANGE
    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/your username cannot be changed right now/i)).toBeVisible();
    expect(
      screen.getByText(
        /you can request another change after your previous request's 30-day cooldown period has ended/i,
      ),
    ).toBeVisible();
  });

  it('given the user can create a username change request, shows the form', () => {
    // ARRANGE
    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ASSERT
    expect(screen.getAllByLabelText(/new username/i)[0]).toBeVisible();
    expect(screen.getByLabelText(/confirm new username/i)).toBeVisible();
  });

  it('given the user attempts to submit with non-matching usernames, does not submit', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'different-name');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user attempts to submit usernames with invalid characters, does not submit', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'TestUser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'new-name');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
    expect(
      screen.getAllByText(/must only contain unaccented letters and numbers./i)[0],
    ).toBeVisible();
  });

  it('given the user submits valid form data but cancels the confirmation, does not submit', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
    const postSpy = vi.spyOn(axios, 'post');

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'NewName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'NewName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user submits valid form data and confirms, sends the request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        success: true,
      },
    });

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'TestUser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'NewName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'NewName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.name-change-request.store'), {
      newDisplayName: 'NewName',
    });
  });

  it('given the API returns a username taken error, shows the appropriate error message', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        data: {
          message: 'has already been taken',
        },
      },
    });

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'TestUser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'NewName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'NewName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/this username is already taken/i)).toBeVisible();
    });
  });

  it('given the API returns an unexpected error, shows a generic error message', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        data: {
          message: 'some other error',
        },
      },
    });

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'TestUser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'NewName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'NewName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the user submits a username that only differs in case, auto-approves without confirmation', async () => {
    // ARRANGE
    delete (window as any).location;
    window.location = {
      ...originalLocation,
      reload: vi.fn(),
    };

    const confirmSpy = vi.spyOn(window, 'confirm');
    const reloadSpy = vi.spyOn(window.location, 'reload').mockImplementation(() => {});
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        success: true,
      },
    });

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'testuser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'TestUser');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'TestUser');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(confirmSpy).not.toHaveBeenCalled();
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.name-change-request.store'), {
      newDisplayName: 'TestUser',
    });
    expect(reloadSpy).toHaveBeenCalled();
  });

  it('given the API returns success for a case change, reloads the page', async () => {
    // ARRANGE
    delete (window as any).location;
    window.location = {
      ...originalLocation,
      reload: vi.fn(),
    };

    const reloadSpy = vi.spyOn(window.location, 'reload').mockImplementation(() => {});
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        success: true,
      },
    });

    render(<ChangeUsernameSectionCard />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'testuser' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'TestUser');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'TestUser');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    await waitFor(() => {
      expect(reloadSpy).toHaveBeenCalled();
    });
  });
});
