import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { KeysSectionCard } from './KeysSectionCard';

vi.mock('react-use', async (importOriginal) => {
  const original: object = await importOriginal();

  return {
    ...original,
    useMedia: vi.fn(),
  };
});

describe('Component: KeysSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: {},
        userSettings: createUser(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the user doesn't have permission to manipulate their API keys, doesn't render", () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: false },
        userSettings: createUser({ apiKey: 'mockApiKey' }),
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /keys/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('has a link to the RetroAchievements API documentation', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser(),
      },
    });

    // ASSERT
    expect(screen.getByRole('link')).toHaveAttribute(
      'href',
      'https://api-docs.retroachievements.org',
    );
  });

  it("shows an obfuscated version of the user's web API key", () => {
    // ARRANGE
    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser({ apiKey: mockApiKey }),
      },
    });

    // ASSERT
    expect(screen.queryByText(mockApiKey)).not.toBeInTheDocument();
    expect(screen.getByText('AAAAAA...BBBBBB')).toBeVisible();
  });

  it('given the user presses the reset web API key button, sends a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset web api key/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('settings.keys.web.destroy'));
  });

  it('given the user resets their web API key, shows their new obfuscated key in the UI', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { newKey: 'BBBBBBxxxxxxxCCCCCC' } });
    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser({ apiKey: mockApiKey }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset web api key/i }));

    // ASSERT
    expect(screen.getByText('BBBBBB...CCCCCC')).toBeVisible();
  });

  it('given the user presses the reset Connect API key button, sends a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset connect api key/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('settings.keys.connect.destroy'));
  });
});
