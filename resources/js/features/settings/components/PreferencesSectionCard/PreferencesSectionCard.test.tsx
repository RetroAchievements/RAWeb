import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { PreferencesSectionCard } from './PreferencesSectionCard';

describe('Component: PreferencesSectionCard', () => {
  // TODO remove when multiset isnt behind a feature flag
  const originalMultisetFlag = import.meta.env.VITE_FEATURE_MULTISET;

  beforeEach(() => {
    import.meta.env.VITE_FEATURE_MULTISET = originalMultisetFlag;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PreferencesSectionCard currentWebsitePrefs={131200} onUpdateWebsitePrefs={vi.fn()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('correctly sets the initial form values', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentWebsitePrefs={131200} onUpdateWebsitePrefs={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('switch', { name: /suppress mature content warnings/i })).toBeChecked();
    expect(screen.getByRole('switch', { name: /prefer absolute dates/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /hide missable/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /only people i follow/i })).toBeChecked();
  });

  it('given the user submits the form, makes the correct request to the server', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard currentWebsitePrefs={139471} onUpdateWebsitePrefs={vi.fn()} />);

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /only people i follow/i }));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      websitePrefs: 8399,
    });
  });

  it('given the user does not have the game subsets opt out setting enabled, shows the toggle as checked', () => {
    // ARRANGE
    import.meta.env.VITE_FEATURE_MULTISET = 'true';

    render(<PreferencesSectionCard currentWebsitePrefs={127} onUpdateWebsitePrefs={vi.fn()} />);

    // ASSERT
    const switchEl = screen.getByRole('switch', { name: /automatically opt in/i });

    expect(switchEl).toBeChecked();
  });

  it('allows the user to change their game subsets opt out preference', async () => {
    // ARRANGE
    vi.stubEnv('VITE_FEATURE_MULTISET', 'true');

    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard currentWebsitePrefs={127} onUpdateWebsitePrefs={vi.fn()} />);

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /automatically opt in/i }));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      websitePrefs: 262271,
    });
  });

  it('given the multiset feature is not available for users, does not show the game sets opt in toggle', () => {
    // ARRANGE
    vi.stubEnv('VITE_FEATURE_MULTISET', '');

    render(<PreferencesSectionCard currentWebsitePrefs={127} onUpdateWebsitePrefs={vi.fn()} />);

    // ASSERT
    expect(screen.queryByRole('switch', { name: /automatically opt in/i })).not.toBeInTheDocument();
  });

  it('given the user has beta features enabled, shows the beta features toggle as checked', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentWebsitePrefs={0} onUpdateWebsitePrefs={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            enableBetaFeatures: true,
            roles: [],
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('switch', { name: /enable beta features/i })).toBeChecked();
  });

  it('given the user does not have beta features enabled, shows the beta features toggle as unchecked', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentWebsitePrefs={0} onUpdateWebsitePrefs={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            enableBetaFeatures: false,
            roles: [],
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('switch', { name: /enable beta features/i })).not.toBeChecked();
  });

  it('given the user toggles beta features, makes the correct requests to the server', async () => {
    // ARRANGE
    const putSpy = vi
      .spyOn(axios, 'put')
      .mockResolvedValueOnce({ success: true }) // !! preferences update
      .mockResolvedValueOnce({ success: true, hasBetaFeatures: true }); // !! beta features toggle

    render(<PreferencesSectionCard currentWebsitePrefs={0} onUpdateWebsitePrefs={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            enableBetaFeatures: false,
            roles: [],
          }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /enable beta features/i }));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledTimes(2);
    expect(putSpy).toHaveBeenNthCalledWith(1, route('api.settings.preferences.update'), {
      websitePrefs: 0,
    });
    expect(putSpy).toHaveBeenNthCalledWith(2, route('api.settings.beta-features.toggle'));
  });

  it('given the user only changes preferences without toggling beta features, only updates preferences', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard currentWebsitePrefs={0} onUpdateWebsitePrefs={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            enableBetaFeatures: false,
            roles: [],
          }),
        },
      },
    });

    // ACT
    await userEvent.click(
      screen.getByRole('switch', { name: /suppress mature content warnings/i }),
    );
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledTimes(1);
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      websitePrefs: 128, // !! only the preference bit changed
    });
  });
});
