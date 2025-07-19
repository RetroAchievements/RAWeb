import userEvent from '@testing-library/user-event';
import axios from 'axios';
import * as ReactUseModule from 'react-use';
import { route } from 'ziggy-js';

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

  it('given the user presses the web API key button, copies the key to the clipboard', async () => {
    // ARRANGE
    const copyToClipboardSpy = vi.fn();
    vi.spyOn(ReactUseModule, 'useCopyToClipboard').mockReturnValueOnce([
      { noUserInteraction: false },
      copyToClipboardSpy,
    ]);

    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser({ apiKey: mockApiKey }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /aaaaaa/i }));

    // ASSERT
    expect(copyToClipboardSpy).toHaveBeenCalledOnce();
    expect(copyToClipboardSpy).toHaveBeenCalledWith(mockApiKey);
  });

  it('given the user hovers over the web API key button, shows a descriptive tooltip', async () => {
    // ARRANGE
    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser({ apiKey: mockApiKey }),
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /aaaaaa/i }));

    // ASSERT
    expect(await screen.findByRole('tooltip', { name: /copy to clipboard/i })).toBeVisible();
  });

  it('given the user is on the XS breakpoint and hovers over the web API key button, does not show a descriptive tooltip', async () => {
    // ARRANGE
    console.error = vi.fn(); // Ignore act() errors.

    vi.spyOn(ReactUseModule, 'useMedia').mockReturnValueOnce(true);

    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser({ apiKey: mockApiKey }),
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /aaaaaa/i }));

    // ASSERT
    await new Promise((r) => setTimeout(r, 1000)); // wait 1 second to ensure tooltip would appear if it was triggered
    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
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
    expect(deleteSpy).toHaveBeenCalledWith(route('api.settings.keys.web.destroy'));
  });

  it('given the user does not confirm they want to reset their web API key, does not send a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
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
    expect(deleteSpy).not.toHaveBeenCalled();
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

  it('given the user presses the sign out of all emulators button, sends a DELETE call to the server', async () => {
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
    await userEvent.click(screen.getByRole('button', { name: /sign out of all emulators/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.settings.keys.connect.destroy'));
  });

  it('given the user does not confirm they want to sign out of all emulators, does not send a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<KeysSectionCard />, {
      pageProps: {
        can: { manipulateApiKeys: true },
        userSettings: createUser(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /sign out of all emulators/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
  });
});
