import { faker } from '@faker-js/faker';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
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
        route('api.user.avatar.store'),
        expect.anything(),
        expect.anything(),
      );
    });
  });

  it('given an error occurs during file reading, pops an error toast', async () => {
    // ARRANGE
    const file = new File(['file content'], 'myfile.png', { type: 'image/png' });

    const mockFileReader = {
      readAsDataURL: vi.fn(),
      onerror: vi.fn(),
      onload: vi.fn(),
    };

    vi.spyOn(window, 'FileReader').mockImplementationOnce(
      () => mockFileReader as unknown as FileReader,
    );

    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ACT
    const fileInputEl = screen.getByLabelText(/new image/i);

    await userEvent.upload(fileInputEl, file);
    await userEvent.click(screen.getByRole('button', { name: /upload/i }));

    // Simulate an error during file reading.
    const error = new Error('File reading error');
    mockFileReader.onerror({ target: { error } } as unknown as ProgressEvent<FileReader>);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
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
    expect(deleteSpy).toHaveBeenCalledWith(route('api.user.avatar.destroy'));
  });

  it('given the user makes the request to set their avatar to default, tries to optimistically reset references to their current avatar in the UI', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    const oldSrc = faker.internet.url();

    render(
      <div>
        <img data-testid="old-avatar" className="userpic" src={oldSrc} />
        <AvatarSection />
      </div>,
      { pageProps: { auth: { user: createAuthenticatedUser() }, can: { updateAvatar: true } } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset avatar to default/i }));

    // ASSERT
    const imgEl = screen.getByTestId('old-avatar');
    expect(imgEl.getAttribute('src')).not.toEqual(oldSrc);
  });

  it('given the user does not confirm they want to reset their avatar to default, does not make a request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset avatar to default/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
  });

  it('given something goes wrong with the API call to reset the avatar to default, pops an error toast', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockRejectedValue({ success: false });

    render(<AvatarSection />, { pageProps: { can: { updateAvatar: true } } });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset avatar to default/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.user.avatar.destroy'));

    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });
});
