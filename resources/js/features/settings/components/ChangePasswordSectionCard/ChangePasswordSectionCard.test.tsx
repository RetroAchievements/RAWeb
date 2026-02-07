import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { ChangePasswordSectionCard } from './ChangePasswordSectionCard';

describe('Component: ChangePasswordSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChangePasswordSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user attempts to submit without a matching password and confirm password, does not submit', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put');

    render(<ChangePasswordSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.type(screen.getByLabelText(/current password/i), 'giraffe-telescope-banana');
    await userEvent.type(screen.getByLabelText(/new password/i), 'walrus-clarinet-sunset');
    await userEvent.type(screen.getByLabelText(/confirm password/i), 'mismatch-password');

    // ASSERT
    expect(putSpy).not.toHaveBeenCalled();
  });

  it('given the user attempts to make a valid form submission, submits the request to the server', async () => {
    // ARRANGE
    // Suppress "Error: Not implemented: navigation (except hash changes)" from vitest.
    console.error = vi.fn();

    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<ChangePasswordSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.type(screen.getByLabelText(/current password/i), 'giraffe-telescope-banana');
    await userEvent.type(screen.getByLabelText(/new password/i), 'walrus-clarinet-sunset');
    await userEvent.type(screen.getByLabelText(/confirm password/i), 'walrus-clarinet-sunset');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.password.update'), {
      currentPassword: 'giraffe-telescope-banana',
      newPassword: 'walrus-clarinet-sunset',
      confirmPassword: 'walrus-clarinet-sunset',
    });
  });

  it('given the server throws an error, pops an error toast', async () => {
    // ARRANGE
    // Suppress "Error: Not implemented: navigation (except hash changes)" from vitest.
    console.error = vi.fn();

    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi
      .spyOn(axios, 'put')
      .mockRejectedValueOnce({ response: { data: { message: 'Something went wrong.' } } });

    render(<ChangePasswordSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ACT
    await userEvent.type(screen.getByLabelText(/current password/i), 'giraffe-telescope-banana');
    await userEvent.type(screen.getByLabelText(/new password/i), 'walrus-clarinet-sunset');
    await userEvent.type(screen.getByLabelText(/confirm password/i), 'walrus-clarinet-sunset');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.password.update'), {
      currentPassword: 'giraffe-telescope-banana',
      newPassword: 'walrus-clarinet-sunset',
      confirmPassword: 'walrus-clarinet-sunset',
    });

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });
});
