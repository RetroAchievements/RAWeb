import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { createSettingsPageProps } from '../../models';
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
    const { container } = render(<KeysSectionCard />, { pageProps: createSettingsPageProps() });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the user doesn't have permission to manipulate their API keys, doesn't render", () => {
    // ARRANGE
    render(<KeysSectionCard />, {
      pageProps: { can: { manipulateApiKeys: false }, userSettings: { apiKey: 'mockApiKey' } },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /keys/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('has a link to the RetroAchievements API documentation', () => {
    // ARRANGE
    render(<KeysSectionCard />, { pageProps: createSettingsPageProps() });

    // ASSERT
    expect(screen.getByRole('link')).toHaveAttribute(
      'href',
      'https://api-docs.retroachievements.org',
    );
  });

  it("shows an obfuscated version of the user's web API key", () => {
    // ARRANGE
    const mockApiKey = 'AAAAAAxxxxxxxxxxBBBBBB';

    render(<KeysSectionCard />, {
      pageProps: { can: { manipulateApiKeys: true }, userSettings: { apiKey: mockApiKey } },
    });

    // ASSERT
    expect(screen.queryByText(mockApiKey)).not.toBeInTheDocument();
    expect(screen.getByText('AAAAAA...BBBBBB')).toBeVisible();
  });

  it('given the user presses the reset web API key button, sends a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete');

    render(<KeysSectionCard />, { pageProps: createSettingsPageProps() });

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

    render(<KeysSectionCard />, {
      pageProps: { can: { manipulateApiKeys: true }, userSettings: { apiKey: mockApiKey } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset web api key/i }));

    // ASSERT
    expect(screen.getByText('BBBBBB...CCCCCC')).toBeVisible();
  });

  it('given the user presses the reset Connect API key button, sends a DELETE call to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete');

    render(<KeysSectionCard />, { pageProps: createSettingsPageProps() });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset connect api key/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('settings.keys.connect.destroy'));
  });
});
