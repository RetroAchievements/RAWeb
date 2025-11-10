import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createNews } from '@/test/factories';

import { LatestSiteUpdatesButton } from './LatestSiteUpdatesButton';

describe('Component: LatestSiteUpdatesButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    render(<LatestSiteUpdatesButton />, {
      pageProps: {
        deferredSiteReleaseNotes: [createNews({ id: 456 }), createNews({ id: 123 })],
        hasUnreadSiteReleaseNote: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /latest site updates/i })).toBeVisible();
  });

  it('given there is an unread release notenote, displays the unread indicator', () => {
    // ARRANGE
    render(<LatestSiteUpdatesButton />, {
      pageProps: {
        deferredSiteReleaseNotes: [],
        hasUnreadSiteReleaseNote: true,
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/unread/i)).toBeVisible();
  });

  it('given there is no unread note, does not display the unread indicator', () => {
    // ARRANGE
    render(<LatestSiteUpdatesButton />, {
      pageProps: {
        deferredSiteReleaseNotes: [],
        hasUnreadSiteReleaseNote: false,
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/unread/i)).not.toBeInTheDocument();
  });

  it('given the user clicks the button, hides the unread indicator', async () => {
    // ARRANGE
    const user = userEvent.setup();

    render(<LatestSiteUpdatesButton />, {
      pageProps: {
        deferredSiteReleaseNotes: [],
        hasUnreadSiteReleaseNote: true, // !!
      },
    });

    expect(screen.getByLabelText(/unread/i)).toBeVisible();

    // ACT
    await user.click(screen.getByRole('button', { name: /latest site updates/i }));

    // ASSERT
    expect(screen.queryByLabelText(/unread/i)).not.toBeInTheDocument();
  });
});
