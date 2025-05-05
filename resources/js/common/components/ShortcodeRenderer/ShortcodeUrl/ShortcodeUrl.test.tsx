import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { ShortcodeUrl } from './ShortcodeUrl';

// Suppress "TypeError: Invalid URL".
console.debug = vi.fn();

describe('Component: ShortcodeUrl', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeUrl href="https://example.com">Click me</ShortcodeUrl>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an internal RetroAchievements URL, renders a direct link', () => {
    // ARRANGE
    render(
      <ShortcodeUrl href="https://retroachievements.org/user/Scott/progress">
        Progress Page
      </ShortcodeUrl>,
    );

    // ASSERT
    const link = screen.getByText(/progress page/i);
    expect(link).toBeVisible();
    expect(link.getAttribute('href')).toEqual('https://retroachievements.org/user/Scott/progress');
    expect(link.getAttribute('rel')).toBeNull();
  });

  it('given an external URL, renders a link through the redirect route', () => {
    // ARRANGE
    render(<ShortcodeUrl href="https://example.com">External link</ShortcodeUrl>);

    // ASSERT
    const link = screen.getByText(/external link/i);
    expect(link).toBeVisible();
    expect(link.getAttribute('href')).toContain(route('redirect'));
    expect(link.getAttribute('rel')).toEqual('nofollow noopener');
  });

  it('given an HTTP internal URL, upgrades it to HTTPS', () => {
    // ARRANGE
    render(
      <ShortcodeUrl href="http://retroachievements.org/game/1/top-achievers">
        HTTP link
      </ShortcodeUrl>,
    );

    // ASSERT
    expect(
      screen.getByTestId(`url-embed-https://retroachievements.org/game/1/top-achievers`),
    ).toBeVisible();
  });

  it('given an invalid URL, attempts to normalize it', () => {
    // ARRANGE
    render(<ShortcodeUrl href="///example.com">Invalid URL</ShortcodeUrl>);

    // ASSERT
    expect(screen.getByTestId(`url-embed-https://example.com/`)).toBeVisible();
  });

  it('given an empty URL, renders broken link text', () => {
    // ARRANGE
    render(<ShortcodeUrl href="">Empty link</ShortcodeUrl>);

    // ASSERT
    expect(screen.getByText(/broken link/i)).toBeVisible();
  });

  it('given a broken URL, renders broken link text', () => {
    // ARRANGE
    render(<ShortcodeUrl href="<a">Empty link</ShortcodeUrl>);

    // ASSERT
    expect(screen.getByText(/broken link/i)).toBeVisible();
  });
});
