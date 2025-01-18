import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { requestedUsernameAtom } from '../../state/settings.atoms';
import { ChangeUsernameSectionCard } from './ChangeUsernameSectionCard';

describe('Component: ChangeUsernameSectionCard', () => {
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
    expect(screen.getByText(/you have an active username change request/i)).toBeVisible();
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
    expect(screen.getByText(/you must wait to change your username/i)).toBeVisible();
    expect(
      screen.getByText(/each account is limited to one username change every 30 days/i),
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
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'new-name');
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
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'new-name');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.settings.username-change-request.store'), {
      newDisplayName: 'new-name',
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
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'new-name');
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
        auth: { user: createAuthenticatedUser({ displayName: 'test-user' }) },
        can: { createUsernameChangeRequest: true },
      },
    });

    // ACT
    await userEvent.type(screen.getAllByLabelText(/new username/i)[0], 'new-name');
    await userEvent.type(screen.getByLabelText(/confirm new username/i), 'new-name');
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });
});
