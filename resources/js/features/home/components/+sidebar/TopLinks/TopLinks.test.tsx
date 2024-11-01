import { render, screen } from '@/test';

import { TopLinks } from './TopLinks';

describe('Component: TopLinks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TopLinks />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible link to the emulator downloads page', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /download emulator/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'download.index');
  });

  it('displays an accessible link to the global points ranking', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /global points ranking/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', '/globalRanking.php');
  });

  it('displays an accessible link to the global beaten games ranking', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /global beaten games ranking/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'ranking.beaten-games');
  });

  it.todo(
    'given there is a Discord invite URL configured, displays an accessible link to the platform Discord server',
  );
  it.todo(
    'given there is a Patreon user ID configured, displays an accessible link to the Patreon page',
  );

  it('displays an accessible link to RANews', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /ranews/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://news.retroachievements.org');
  });

  it('displays an accessible link to RAPodcast', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /rapodcast/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://www.youtube.com/@RAPodcast');
  });

  it('displays an accessible link to the RetroAchievements documentation', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /documentation/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://docs.retroachievements.org');
  });

  it('displays an accessible link to the RetroAchievements documentation FAQ page', () => {
    // ARRANGE
    render(<TopLinks />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /faq/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'https://docs.retroachievements.org/general/faq.html');
  });
});
