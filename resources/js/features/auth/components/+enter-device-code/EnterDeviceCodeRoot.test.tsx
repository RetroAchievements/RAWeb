import { render, screen } from '@/test';

import { EnterDeviceCodeRoot } from './EnterDeviceCodeRoot';

vi.mock('../OAuthPageLayout', () => ({
  OAuthPageLayout: ({ children, glowVariant, initial }: any) => (
    <div
      data-testid="oauth-page-layout"
      data-glow-variant={glowVariant}
      data-initial={JSON.stringify(initial)}
    >
      {children}
    </div>
  ),
}));

describe('Component: EnterDeviceCodeRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given flash status is authorization-approved, renders the success component', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: { status: 'authorization-approved' }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/authorized/i)).toBeVisible();
    expect(screen.getByText(/you can close this window/i)).toBeVisible();

    const layout = screen.getByTestId('oauth-page-layout');
    expect(layout).toHaveAttribute('data-glow-variant', 'success');
  });

  it('given flash status is authorization-denied, renders the denied component', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: { status: 'authorization-denied' }, // !!
      },
    });

    // ASSERT
    expect(screen.getByText(/denied/i)).toBeVisible();
    expect(screen.getByText(/you can close this window/i)).toBeVisible();

    const layout = screen.getByTestId('oauth-page-layout');
    expect(layout).toHaveAttribute('data-glow-variant', 'error');
  });

  it('given no special flash status, renders the enter code form', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: {},
      },
    });

    // ASSERT
    expect(screen.getByText(/link your app/i)).toBeVisible();
    expect(
      screen.getByText(/enter the code shown in the app you want to connect to retroachievements/i),
    ).toBeVisible();
    expect(screen.getByLabelText(/one-time code/i)).toBeVisible();
    expect(screen.getByPlaceholderText(/XXXX-XXXX/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /connect/i })).toBeVisible();
  });

  it('given there is a user_code error, shows the error message in the form', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: { user_code: 'Invalid code' }, // !!
        flash: {},
      },
    });

    // ASSERT
    expect(screen.getByText(/incorrect code/i)).toBeVisible();
  });

  it('given there is no user_code error, does not show an error message', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: {},
      },
    });

    // ASSERT
    expect(screen.queryByText(/incorrect code/i)).not.toBeInTheDocument();
  });

  it('given there is a user_code error, disables initial animation in OAuthPageLayout', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: { user_code: 'Invalid code' }, // !!
        flash: {},
      },
    });

    // ASSERT
    const layout = screen.getByTestId('oauth-page-layout');
    expect(layout).toHaveAttribute('data-initial', 'false');
  });

  it('given there is no user_code error, sets initial animation in OAuthPageLayout', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: {},
      },
    });

    // ASSERT
    const layout = screen.getByTestId('oauth-page-layout');
    expect(layout).toHaveAttribute('data-initial', '{"opacity":0,"y":12}');
  });

  it('given flash status is authorization-approved, does not render the form', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: { status: 'authorization-approved' },
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/one-time code/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /connect/i })).not.toBeInTheDocument();
  });

  it('given flash status is authorization-denied, does not render the form', () => {
    // ARRANGE
    render(<EnterDeviceCodeRoot />, {
      pageProps: {
        errors: {},
        flash: { status: 'authorization-denied' },
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/one-time code/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /connect/i })).not.toBeInTheDocument();
  });
});
