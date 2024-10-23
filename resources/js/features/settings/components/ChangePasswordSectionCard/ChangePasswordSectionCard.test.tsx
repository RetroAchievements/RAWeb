import userEvent from '@testing-library/user-event';
import axios from 'axios';

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
    await userEvent.type(screen.getByLabelText(/current password/i), '12345678');
    await userEvent.type(screen.getByLabelText(/new password/i), '87654321');
    await userEvent.type(screen.getByLabelText(/confirm password/i), 'aaaaaaaa');

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
    await userEvent.type(screen.getByLabelText(/current password/i), '12345678');
    await userEvent.type(screen.getByLabelText(/new password/i), '87654321');
    await userEvent.type(screen.getByLabelText(/confirm password/i), '87654321');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.password.update'), {
      currentPassword: '12345678',
      newPassword: '87654321',
      confirmPassword: '87654321',
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
    await userEvent.type(screen.getByLabelText(/current password/i), '12345678');
    await userEvent.type(screen.getByLabelText(/new password/i), '87654321');
    await userEvent.type(screen.getByLabelText(/confirm password/i), '87654321');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.password.update'), {
      currentPassword: '12345678',
      newPassword: '87654321',
      confirmPassword: '87654321',
    });

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });
});
