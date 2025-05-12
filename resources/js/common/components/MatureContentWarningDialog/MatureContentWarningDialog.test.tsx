import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { MatureContentWarningDialog } from './MatureContentWarningDialog';

describe('Component: ContentWarningDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not opted to bypass content warnings, shows the dialog', () => {
    // ARRANGE
    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: {
              prefersAbsoluteDates: false,
              shouldAlwaysBypassContentWarnings: false, // !!
            },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('alertdialog')).toBeVisible();

    expect(screen.getByText(/content warning/i)).toBeVisible();
    expect(screen.getByText(/are you sure you want to view this page/i)).toBeVisible();
  });

  it('given the user has opted to bypass content warnings, does not show the dialog', () => {
    // ARRANGE
    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: {
              prefersAbsoluteDates: false,
              shouldAlwaysBypassContentWarnings: true, // !!
            },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
  });

  it('given the user clicks "Yes, I\'m an adult", closes the dialog without making any API calls', async () => {
    // ARRANGE
    const patchSpy = vi.spyOn(axios, 'patch');

    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /yes, i'm an adult/i }));

    // ASSERT
    expect(patchSpy).not.toHaveBeenCalled();
    expect(screen.queryByText(/content warning/i)).not.toBeInTheDocument();
  });

  it('given the user clicks "Yes, and don\'t ask again", makes an API call and closes the dialog', async () => {
    // ARRANGE
    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({});

    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /yes, and don't ask again/i }));

    // ASSERT
    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith(
        route('api.settings.preferences.suppress-mature-content-warning'),
      );
    });

    expect(screen.queryByText(/content warning/i)).not.toBeInTheDocument();
  });

  it('given the user clicks "No", redirects them to the specified URL', async () => {
    // ARRANGE
    const mockLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });

    const customUrl = '/custom-url';

    render(<MatureContentWarningDialog noHref={customUrl} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /no/i }));

    // ASSERT
    expect(mockLocationAssign).toHaveBeenCalledWith(customUrl);
  });

  it('given the user clicks "No" without a specified URL, redirects them to the home page', async () => {
    // ARRANGE
    const mockLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });

    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /no/i }));

    // ASSERT
    expect(mockLocationAssign).toHaveBeenCalledWith(route('home'));
  });

  it('given the user presses the escape key, still does not close the dialog', async () => {
    // ARRANGE
    render(<MatureContentWarningDialog />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ACT
    await userEvent.keyboard('{Escape}');

    // ASSERT
    expect(screen.getByRole('alertdialog')).toBeVisible();
    expect(screen.getByText(/content warning/i)).toBeVisible();
  });
});
