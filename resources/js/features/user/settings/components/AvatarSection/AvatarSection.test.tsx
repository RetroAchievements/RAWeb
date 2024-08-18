import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';

import { AvatarSection } from './AvatarSection';

describe('Component: AvatarSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AvatarSection />, { pageProps: { can: {} } });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user does not have permission to upload an avatar, tells them', () => {
    // ARRANGE
    render(<AvatarSection />, { pageProps: { can: { updateAvatar: false } } });

    // ASSERT
    expect(screen.queryByLabelText(/new image/i)).not.toBeInTheDocument();
    expect(screen.getByText(/earn 250 points or wait/i)).toBeVisible();
  });

  it('given the user has permission to upload an avatar, shows the file input field', () => {
    // ARRANGE
    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ASSERT
    expect(screen.getByLabelText(/new image/i)).toBeVisible();
  });

  it('given the user tries to submit a new avatar image, attempts to upload it to the server', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const file = new File(['file content'], 'myfile.png', { type: 'image/png' });

    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ACT
    const fileInputEl = screen.getByLabelText(/new image/i);

    await userEvent.upload(fileInputEl, file);
    await userEvent.click(screen.getByRole('button', { name: /upload/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('user.avatar.store'),
        expect.anything(),
        expect.anything(),
      );
    });
  });

  it('given the user tries to reset their avatar to the default, makes the request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset avatar to default/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('user.avatar.destroy'));
  });
});
