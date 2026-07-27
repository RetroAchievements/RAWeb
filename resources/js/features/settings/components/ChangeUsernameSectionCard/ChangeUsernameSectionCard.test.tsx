import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { requestedUsernameAtom } from '../../state/settings.atoms';
import { ChangeUsernameSectionCard } from './ChangeUsernameSectionCard';

// Suppress setState() warnings that only happen in JSDOM.
console.error = vi.fn();

describe('Component: ChangeUsernameSectionCard', () => {
  const originalLocation = window.location;

  afterEach(() => {
    (window as any).location = originalLocation;
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
      jotaiAtoms: [
        [requestedUsernameAtom, 'new-username'],
        //
      ],
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
    expect(screen.getByPlaceholderText('Enter your new username')).toBeVisible();
    expect(screen.getByPlaceholderText('Confirm your new username')).toBeVisible();
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

  it('given the user submits valid form data, opens the confirmation dialog without submitting', async () => {
    // ARRANGE
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
    expect(screen.getByRole('heading', { name: /is this right/i })).toBeVisible();
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user cancels the confirmation dialog, does not submit and preserves the typed values', async () => {
    // ARRANGE
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
    await userEvent.click(screen.getByRole('button', { name: /cancel/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();

    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    expect(screen.getAllByLabelText(/new username/i)[0]).toHaveValue('NewName');
    expect(screen.getByLabelText(/confirm new username/i)).toHaveValue('NewName');
  });

  it('given the user cancels and resubmits, reopens the dialog with the acknowledgement value reset', async () => {
    // ARRANGE
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
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /cancel/i }));

    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(screen.getByRole('checkbox')).not.toBeChecked();
    expect(screen.getByRole('button', { name: /change name/i })).toBeDisabled();
  });

  it('given the user acknowledges and confirms, sends the request and closes the dialog', async () => {
    // ARRANGE
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
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.name-change-request.store'), {
      newDisplayName: 'NewName',
    });

    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('given the user cancels and retypes a different username, confirms and submits the new one', async () => {
    // ARRANGE
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

    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'FirstName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'FirstName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));
    await userEvent.click(screen.getByRole('button', { name: /cancel/i }));

    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    await userEvent.clear(screen.getAllByLabelText(/new username/i)[0]);
    await userEvent.clear(screen.getByLabelText(/confirm new username/i));
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'SecondName');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'SecondName');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    expect(screen.getByText('SecondName')).toBeVisible();
    expect(screen.queryByText('FirstName')).not.toBeInTheDocument();

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.name-change-request.store'), {
      newDisplayName: 'SecondName',
    });
  });

  it('given the dialog is open, shows the requested username so the user can check it', async () => {
    // ARRANGE
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
    expect(screen.getByText('NewName')).toBeVisible();
    expect(screen.getByText(/can't ask again for 30 days/i)).toBeVisible();
  });

  it('given the API returns a username taken error, shows the appropriate error message', async () => {
    // ARRANGE
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
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/this username is already taken/i)).toBeVisible();
    });
  });

  it('given the API returns a username not available error, shows the appropriate error message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        data: {
          message: 'not available',
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
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/this username is not available/i)).toBeVisible();
    });
  });

  it('given the API returns an unexpected error, shows a generic error message', async () => {
    // ARRANGE
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
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByRole('checkbox')).toBeChecked();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /change name/i })).toBeEnabled();
    });
  });

  it('given the user submits a username that only differs in case, auto-approves without the dialog', async () => {
    // ARRANGE
    delete (window as any).location;
    (window as any).location = {
      ...originalLocation,
      reload: vi.fn(),
    };

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
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.name-change-request.store'), {
      newDisplayName: 'TestUser',
    });
    expect(reloadSpy).toHaveBeenCalled();
  });

  it('given the API returns success for a case change, reloads the page', async () => {
    // ARRANGE
    delete (window as any).location;
    (window as any).location = {
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
