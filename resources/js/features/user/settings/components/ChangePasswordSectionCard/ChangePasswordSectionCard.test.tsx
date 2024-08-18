import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { ChangePasswordSectionCard } from './ChangePasswordSectionCard';

describe('Component: ChangePasswordSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChangePasswordSectionCard />, {
      pageProps: {},
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user attempts to submit without a matching password and confirm password, does not submit', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put');

    render(<ChangePasswordSectionCard />, {
      pageProps: {},
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
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put');

    render(<ChangePasswordSectionCard />, {
      pageProps: {},
    });

    // ACT
    await userEvent.type(screen.getByLabelText(/current password/i), '12345678');
    await userEvent.type(screen.getByLabelText(/new password/i), '87654321');
    await userEvent.type(screen.getByLabelText(/confirm password/i), '87654321');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('settings.password.update'), {
      currentPassword: '12345678',
      newPassword: '87654321',
      confirmPassword: '87654321',
    });
  });
});
