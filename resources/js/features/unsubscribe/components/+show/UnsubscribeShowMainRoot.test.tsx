import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';

import { UnsubscribeShowMainRoot } from './UnsubscribeShowMainRoot';

describe('Component: UnsubscribeShowMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UnsubscribeShowMainRoot />, {
      pageProps: {
        success: true,
        undoToken: null,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given success is true and undoSuccess is false, displays the UnsubscribeSuccessCard', () => {
    // ARRANGE
    render(<UnsubscribeShowMainRoot />, {
      pageProps: {
        success: true,
        undoToken: 'some-token',
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage all email preferences/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /undo/i })).toBeVisible();
  });

  it('given success is false, displays the UnsubscribeErrorCard', () => {
    // ARRANGE
    render(<UnsubscribeShowMainRoot />, {
      pageProps: {
        success: false,
        undoToken: null,
        error: 'invalid-token',
      },
    });

    // ASSERT
    expect(screen.getByText(/unable to unsubscribe/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /go to settings/i })).toBeVisible();
  });

  it('given the user clicks undo and the mutation succeeds, displays the UnsubscribeUndoSuccessCard', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });

    render(<UnsubscribeShowMainRoot />, {
      pageProps: {
        success: true,
        undoToken: 'undo-token-123',
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /undo/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/your subscription has been restored/i)).toBeVisible();
    });

    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith(['api.unsubscribe.undo', { token: 'undo-token-123' }]);
  });

  it('given the user clicks undo and the mutation fails, shows an error toast', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('API Error'));

    render(<UnsubscribeShowMainRoot />, {
      pageProps: {
        success: true,
        undoToken: 'undo-token-123',
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /undo/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });

    expect(postSpy).toHaveBeenCalledOnce();
  });
});
