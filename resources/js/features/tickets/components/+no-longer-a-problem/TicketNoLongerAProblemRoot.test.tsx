import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';

import { TicketNoLongerAProblemRoot } from './TicketNoLongerAProblemRoot';

describe('Component: TicketNoLongerAProblemRoot', () => {
  it('given a closable ticket and a reporter who confirms, posts to the signed close URL and reports the ticket as closed', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });

    render<App.Platform.Data.TicketNoLongerAProblemPageProps>(<TicketNoLongerAProblemRoot />, {
      pageProps: {
        canClose: true,
        closeUrl: 'https://example.com/close?signature=abc',
        gameTitle: 'Undertale',
        ticketableTitle: 'Determination',
      },
    });

    expect(screen.getByText(/working for you now/)).toHaveTextContent(
      'Is Determination (Undertale) working for you now?',
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Yes, close my ticket' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText('Thanks for letting us know. Your ticket is closed.')).toBeVisible();
    });
    expect(postSpy).toHaveBeenCalledWith('https://example.com/close?signature=abc');
    expect(screen.queryByRole('button', { name: 'Yes, close my ticket' })).not.toBeInTheDocument();
  });

  it('given the close request fails, keeps the button visible and pops an error', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('API Error'));

    render<App.Platform.Data.TicketNoLongerAProblemPageProps>(<TicketNoLongerAProblemRoot />, {
      pageProps: {
        canClose: true,
        closeUrl: 'https://example.com/close',
        gameTitle: 'Undertale',
        ticketableTitle: 'Determination',
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Yes, close my ticket' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
    expect(screen.getByRole('button', { name: 'Yes, close my ticket' })).toBeVisible();
  });

  it('given the ticket is already finished, offers no way to close it', () => {
    // ARRANGE
    render<App.Platform.Data.TicketNoLongerAProblemPageProps>(<TicketNoLongerAProblemRoot />, {
      pageProps: {
        canClose: false,
        closeUrl: 'https://example.com/close',
        gameTitle: 'Undertale',
        ticketableTitle: 'Determination',
      },
    });

    // ASSERT
    expect(screen.getByText('This ticket is already closed.')).toBeVisible();
    expect(screen.queryByRole('button', { name: 'Yes, close my ticket' })).not.toBeInTheDocument();
  });
});
