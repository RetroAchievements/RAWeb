import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import { route } from 'ziggy-js';

import { act, render, screen, waitFor } from '@/test';

import { EnterDeviceCodeForm } from './EnterDeviceCodeForm';

describe('Component: EnterDeviceCodeForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EnterDeviceCodeForm />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the one-time code input field, the connect button, and the trust warning message', () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    // ASSERT
    expect(screen.getByLabelText(/one-time code/i)).toBeVisible();
    expect(screen.getByPlaceholderText(/XXXX-XXXX/i)).toBeVisible();

    expect(screen.getByRole('button', { name: /connect/i })).toBeVisible();

    expect(screen.getByText(/make sure you're authorizing an app you trust/i)).toBeVisible();
  });

  it('given the user types lowercase letters, converts them to uppercase', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    // ACT
    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    await userEvent.type(input, 'abcd');

    // ASSERT
    expect(input).toHaveValue('ABCD');
  });

  it('given the user types non-alphanumeric characters, removes them', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    // ACT
    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    await userEvent.type(input, 'AB!@#CD');

    // ASSERT
    expect(input).toHaveValue('ABCD');
  });

  it('given the user types more than 4 characters, inserts a dash after the fourth character', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    // ACT
    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    await userEvent.type(input, 'ABCD1234');

    // ASSERT
    expect(input).toHaveValue('ABCD-1234');
  });

  it('given the user types more than 8 characters, limits the input to 8 characters', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    // ACT
    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    await userEvent.type(input, 'ABCD1234567890');

    // ASSERT
    expect(input).toHaveValue('ABCD-1234');
  });

  it('given the user submits a valid code, calls router.visit with the correct parameters', async () => {
    // ARRANGE
    vi.mocked(route).mockReturnValue(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
    );

    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABCD1234');
    await userEvent.click(submitButton);

    // ASSERT
    await waitFor(() => {
      expect(route).toHaveBeenCalledWith('passport.device.authorizations.authorize', {
        user_code: 'ABCD1234', // !! Dash removed.
      });
    });

    expect(router.visit).toHaveBeenCalledWith(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
      expect.objectContaining({
        onError: expect.any(Function),
        onFinish: expect.any(Function),
      }),
    );
  });

  it('given the user submits a code that is too short, shows a validation error', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABC'); // !! Only 3 characters.
    await userEvent.click(submitButton);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/your one-time code must be 8 characters/i)).toBeVisible();
    });
  });

  it('given the form is being submitted, disables the submit button', async () => {
    // ARRANGE
    let visitCallback: any;
    vi.mocked(router.visit).mockImplementation((url, options) => {
      visitCallback = options;
    });
    vi.mocked(route).mockReturnValue(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
    );

    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABCD1234');
    await userEvent.click(submitButton);

    // ASSERT
    await waitFor(() => {
      expect(submitButton).toBeDisabled();
    });

    // Clean up - call onFinish to reset the state.
    act(() => {
      visitCallback?.onFinish?.();
    });
  });

  it('given the server returns an error, displays the error message', async () => {
    // ARRANGE
    let visitCallback: any;
    vi.mocked(router.visit).mockImplementation((url, options) => {
      visitCallback = options;
    });
    vi.mocked(route).mockReturnValue(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
    );

    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABCD1234');
    await userEvent.click(submitButton);

    // Simulate server error.
    act(() => {
      visitCallback?.onError?.({ user_code: 'Invalid code provided' });
    });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/invalid code provided/i)).toBeVisible();
    });
  });

  it('given the server returns a generic error, displays it on the userCode field', async () => {
    // ARRANGE
    let visitCallback: any;
    vi.mocked(router.visit).mockImplementation((url, options) => {
      visitCallback = options;
    });
    vi.mocked(route).mockReturnValue(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
    );

    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABCD1234');
    await userEvent.click(submitButton);

    // Simulate server error without user_code field.
    act(() => {
      visitCallback?.onError?.({ general: 'Something went wrong' }); // !! No user_code field.
    });

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the serverError prop is provided, displays the error message', () => {
    // ARRANGE
    render(<EnterDeviceCodeForm serverError="Invalid code" />);

    // ASSERT
    expect(screen.getByText(/incorrect code/i)).toBeVisible();
  });

  it('given the form submission finishes, re-enables the submit button', async () => {
    // ARRANGE
    let visitCallback: any;
    vi.mocked(router.visit).mockImplementation((url, options) => {
      visitCallback = options;
    });
    vi.mocked(route).mockReturnValue(
      '/passport/device/authorizations/authorize?user_code=ABCD1234',
    );

    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);
    const submitButton = screen.getByRole('button', { name: /connect/i });

    // ACT
    await userEvent.type(input, 'ABCD1234');
    await userEvent.click(submitButton);

    // Button should be disabled during submission.
    await waitFor(() => {
      expect(submitButton).toBeDisabled();
    });

    // Simulate request finishing.
    act(() => {
      visitCallback?.onFinish?.();
    });

    // ASSERT
    await waitFor(() => {
      expect(submitButton).not.toBeDisabled();
    });
  });

  it('given the user clears the input, removes the dash', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);

    // ACT
    await userEvent.type(input, 'ABCD1234');
    expect(input).toHaveValue('ABCD-1234');

    await userEvent.clear(input);
    await userEvent.type(input, 'XY');

    // ASSERT
    expect(input).toHaveValue('XY'); // !! No dash for less than 4 characters.
  });

  it('given the user types exactly 4 characters, does not add a dash', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);

    // ACT
    await userEvent.type(input, 'ABCD');

    // ASSERT
    expect(input).toHaveValue('ABCD'); // !! No dash yet.
  });

  it('given the user types the fifth character, adds the dash', async () => {
    // ARRANGE
    render(<EnterDeviceCodeForm />);

    const input = screen.getByPlaceholderText(/XXXX-XXXX/i);

    // ACT
    await userEvent.type(input, 'ABCD1');

    // ASSERT
    expect(input).toHaveValue('ABCD-1'); // !! Dash added.
  });
});
