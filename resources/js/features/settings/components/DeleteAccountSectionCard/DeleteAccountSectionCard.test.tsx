import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';

import { DeleteAccountSectionCard } from './DeleteAccountSectionCard';

describe('Component: DeleteAccountSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DeleteAccountSectionCard />, {
      pageProps: { userSettings: { deleteRequested: null } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not yet requested deletion, does not show a notice indicating they have', () => {
    // ARRANGE
    render(<DeleteAccountSectionCard />, {
      pageProps: { userSettings: { deleteRequested: null } },
    });

    // ASSERT
    expect(screen.queryByText(/you've requested account deletion/i)).not.toBeInTheDocument();

    expect(screen.getByRole('button', { name: /request account deletion/i })).toBeVisible();
  });

  it('given the user has requested deletion, shows a notice and a cancel button', () => {
    // ARRANGE
    render(<DeleteAccountSectionCard />, {
      pageProps: { userSettings: { deleteRequested: new Date('2024-09-01').toISOString() } },
    });

    // ASSERT
    expect(screen.getByText(/you've requested account deletion/i)).toBeVisible();
    expect(screen.getByText(/will be permanently deleted on/i)).toBeVisible();

    expect(
      screen.queryByRole('button', { name: /request account deletion/i }),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: /cancel account deletion request/i })).toBeVisible();
  });

  it('given the user requests account deletion, correctly sends the deletion request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValue({ success: true });

    render(<DeleteAccountSectionCard />, {
      pageProps: { userSettings: { deleteRequested: null } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /request account deletion/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('user.delete-request.store'));
    expect(screen.getByText(/you've requested account deletion/i)).toBeVisible();
  });

  it('given the user requests to cancel account deletion, correctly sends the cancellation request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render(<DeleteAccountSectionCard />, {
      pageProps: { userSettings: { deleteRequested: new Date('2024-09-01').toISOString() } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel account deletion request/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('user.delete-request.destroy'));
    expect(screen.queryByText(/you've requested account deletion/i)).not.toBeInTheDocument();
  });
});
