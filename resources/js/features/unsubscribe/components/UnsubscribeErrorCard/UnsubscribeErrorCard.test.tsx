import { render, screen } from '@/test';

import { UnsubscribeErrorCard } from './UnsubscribeErrorCard';

describe('Component: UnsubscribeErrorCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UnsubscribeErrorCard />, {
      pageProps: {
        error: null,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the unable to unsubscribe message', () => {
    // ARRANGE
    render(<UnsubscribeErrorCard />, {
      pageProps: {
        error: null,
      },
    });

    // ASSERT
    expect(screen.getByText(/unable to unsubscribe/i)).toBeVisible();
  });

  it('given an error, displays the corresponding error message', () => {
    // ARRANGE
    render(<UnsubscribeErrorCard />, {
      pageProps: {
        error: 'expired',
      },
    });

    // ASSERT
    expect(screen.getByText(/undo link has expired/i)).toBeVisible();
  });

  it('given no error, does not display an error message', () => {
    // ARRANGE
    render(<UnsubscribeErrorCard />, {
      pageProps: {
        error: null,
      },
    });

    // ASSERT
    expect(screen.queryByText(/unsubscribeError-/i)).not.toBeInTheDocument();
  });

  it('displays the go to settings link with correct href', () => {
    // ARRANGE
    render(<UnsubscribeErrorCard />, {
      pageProps: {
        error: null,
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /go to settings/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', 'settings.show');
  });
});
