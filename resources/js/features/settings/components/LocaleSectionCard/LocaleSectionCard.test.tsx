import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';

import { LocaleSectionCard } from './LocaleSectionCard';

describe('Component: LocaleSectionCard', () => {
  const originalLocation = window.location;

  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  afterEach(() => {
    window.location = originalLocation;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('correctly sets the initial form values if the user has never previously set their locale', () => {
    // ARRANGE
    render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: '' }) } },
    });

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /current locale/i });

    expect(comboboxEl).toBeVisible();
    expect(screen.getAllByText(/english/i).length).toBeGreaterThanOrEqual(2); // there's an option and a visible label
    expect(screen.getAllByText(/brasil/i).length).toEqual(1); // there's an option, but it's hidden
  });

  it('correctly sets the initial form values if the user has previously set their locale', () => {
    // ARRANGE
    render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: 'pt_BR' }) } },
    });

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /current locale/i });

    expect(comboboxEl).toBeVisible();
    expect(screen.getAllByText(/brasil/i).length).toBeGreaterThanOrEqual(2); // there's an option and a visible label
    expect(screen.getAllByText(/english/i).length).toEqual(1); // there's an option, but it's hidden
  });

  it('displays an accessible link to the translations documentation', () => {
    // ARRANGE
    render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /here/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute(
      'href',
      'https://github.com/RetroAchievements/RAWeb/blob/master/docs/TRANSLATIONS.md',
    );
  });

  it('given the user submits the form, makes the correct request to the server', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: '' }) } },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /current locale/i }));
    await userEvent.click(await screen.findByRole('option', { name: /brasil/i }));

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledOnce();
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.locale.update'), { locale: 'pt_BR' });
  });

  it('given the user submits the form, refreshes the page', async () => {
    // ARRANGE
    delete (window as any).location;
    window.location = {
      ...originalLocation,
      reload: vi.fn(),
    };

    vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<LocaleSectionCard />, {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: '' }) } },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /current locale/i }));
    await userEvent.click(await screen.findByRole('option', { name: /brasil/i }));

    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    await waitFor(() => {
      expect(window.location.reload).toHaveBeenCalledOnce();
    });
  });
});
