import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { PreferencesSectionCard } from './PreferencesSectionCard';

describe('Component: PreferencesSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PreferencesSectionCard />, {
      pageProps: {
        auth: { user: { websitePrefs: 131200 } },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('correctly sets the initial form values', () => {
    // ARRANGE
    render(<PreferencesSectionCard />, {
      pageProps: {
        auth: { user: { websitePrefs: 131200 } },
      },
    });

    // ASSERT
    expect(screen.getByRole('switch', { name: /suppress mature content warnings/i })).toBeChecked();
    expect(screen.getByRole('switch', { name: /show absolute dates/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /hide missable/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /only people i follow/i })).toBeChecked();
  });

  it('given the user submits the form, makes the correct request to the server', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard />, {
      pageProps: {
        auth: { user: { websitePrefs: 139471 } },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /only people i follow/i }));

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('settings.preferences.update'), {
      websitePrefs: 8399,
    });
  });
});
