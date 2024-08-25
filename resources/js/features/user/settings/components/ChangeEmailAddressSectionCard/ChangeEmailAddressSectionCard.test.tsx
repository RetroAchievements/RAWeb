import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';

import { ChangeEmailAddressSectionCard } from './ChangeEmailAddressSectionCard';

describe('Component: ChangeEmailAddressSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChangeEmailAddressSectionCard />, {
      pageProps: {
        userSettings: { emailAddress: 'foo@bar.com' },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("displays the user's current email address on the screen", () => {
    // ARRANGE
    render(<ChangeEmailAddressSectionCard />, {
      pageProps: {
        userSettings: { emailAddress: 'foo@bar.com' },
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/current email address/i)).toHaveTextContent('foo@bar.com');
  });

  it('given the user attempts to submit without a matching email and confirm email, does not submit', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put');

    render(<ChangeEmailAddressSectionCard />, {
      pageProps: {
        userSettings: { emailAddress: 'foo@bar.com' },
      },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('New Email Address'), 'bar@baz.com');
    await userEvent.type(screen.getByLabelText('Confirm New Email Address'), 'aaaaa@bbbbb.com');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).not.toHaveBeenCalled();
  });

  it('given the user attempts to submit without a valid email, does not submit', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put');

    render(<ChangeEmailAddressSectionCard />, {
      pageProps: {
        userSettings: { emailAddress: 'foo@bar.com' },
      },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('New Email Address'), 'asdfasdfasdf');
    await userEvent.type(screen.getByLabelText('Confirm New Email Address'), 'asdfasdfasdf');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).not.toHaveBeenCalled();
  });

  it('given the user attempts to submit with valid form input, submits the data to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<ChangeEmailAddressSectionCard />, {
      pageProps: {
        userSettings: { emailAddress: 'foo@bar.com' },
      },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('New Email Address'), 'valid@email.com');
    await userEvent.type(screen.getByLabelText('Confirm New Email Address'), 'valid@email.com');

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('settings.email.update'), {
      newEmail: 'valid@email.com',
      // this is sent to the server out of convenience, but the API layer doesn't actually use it
      confirmEmail: 'valid@email.com',
    });
  });
});
