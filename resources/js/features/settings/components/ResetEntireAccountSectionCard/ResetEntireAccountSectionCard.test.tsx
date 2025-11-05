import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { __UNSAFE_VERY_DANGEROUS_SLEEP, render, screen } from '@/test';

import { ResetEntireAccountSectionCard } from './ResetEntireAccountSectionCard';

describe('Component: ResetEntireAccountSectionCard', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetEntireAccountSectionCard />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the action button and the heading', () => {
    // ARRANGE
    render(<ResetEntireAccountSectionCard />);

    // ASSERT
    expect(screen.getByRole('button', { name: /reset entire account/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /reset entire account/i })).toBeVisible();
  });

  it('given the user confirms both dialogs, sends the API request and shows a success message', async () => {
    // ARRANGE
    const confirmSpy = vi.spyOn(window, 'confirm');
    confirmSpy.mockReturnValueOnce(true); // !! first confirmation accepted
    confirmSpy.mockReturnValueOnce(true); // !! second confirmation accepted

    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });

    render(<ResetEntireAccountSectionCard />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset entire account/i }));

    // ASSERT
    expect(confirmSpy).toHaveBeenCalledTimes(2);
    expect(deleteSpy).toHaveBeenCalledTimes(1);
    expect(await screen.findByText(/your entire account progress has been reset/i)).toBeVisible();
  });

  it('given the user cancels the first confirmation, does not send the API request', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete');
    const confirmSpy = vi.spyOn(window, 'confirm');
    confirmSpy.mockReturnValueOnce(false); // !! cancelled

    render(<ResetEntireAccountSectionCard />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset entire account/i }));

    // ASSERT
    expect(confirmSpy).toHaveBeenCalledTimes(1); // !! only the first confirmation was shown
    expect(deleteSpy).not.toHaveBeenCalled();
  });

  it('given user cancels the second confirmation, does not send the API request', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete');
    const confirmSpy = vi.spyOn(window, 'confirm');
    confirmSpy.mockReturnValueOnce(true); // !! first confirmation accepted
    confirmSpy.mockReturnValueOnce(false); // !! second confirmation cancelled

    render(<ResetEntireAccountSectionCard />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset entire account/i }));

    // ASSERT
    expect(confirmSpy).toHaveBeenCalledTimes(2); // !! both confirmations were shown
    expect(deleteSpy).not.toHaveBeenCalled();
  });

  it('given the API request fails, shows an error message', async () => {
    // ARRANGE
    const confirmSpy = vi.spyOn(window, 'confirm');
    confirmSpy.mockReturnValueOnce(true);
    confirmSpy.mockReturnValueOnce(true);

    vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Server error'));

    render(<ResetEntireAccountSectionCard />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset entire account/i }));

    // ASSERT
    expect(await screen.findByText(/something went wrong/i)).toBeVisible();
  });

  it('given the API request succeeds, triggers a page reload after a short delay', async () => {
    // ARRANGE
    const confirmSpy = vi.spyOn(window, 'confirm');
    confirmSpy.mockReturnValueOnce(true);
    confirmSpy.mockReturnValueOnce(true);

    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });

    const originalLocation = window.location;
    const reloadSpy = vi.fn();
    delete (window as any).location;
    (window as any).location = { ...originalLocation, reload: reloadSpy } as Location;

    render(<ResetEntireAccountSectionCard />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset entire account/i }));

    // ... wait for the success message ...
    await screen.findByText(/your entire account progress has been reset/i);
    await __UNSAFE_VERY_DANGEROUS_SLEEP(2100);

    // ASSERT
    expect(reloadSpy).toHaveBeenCalled();

    (window as any).location = originalLocation;
  });
});
