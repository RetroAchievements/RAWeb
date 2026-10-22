import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { FormatNewlines } from './FormatNewlines';

describe('Component: FormatNewlines', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<FormatNewlines>This is\nsome content</FormatNewlines>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('adds line breaks to text with newline characters', () => {
    // ARRANGE
    const { container } = render(<FormatNewlines>This is\nsome content</FormatNewlines>);

    // ASSERT
    // eslint-disable-next-line testing-library/no-container -- not queryable with testing-library
    const brEl = container.querySelectorAll('br');

    expect(brEl.length).toEqual(1);
  });

  it('given text containing an external URL, links it via the redirect route and preserves the surrounding prose', () => {
    // ARRANGE
    render(
      <FormatNewlines>{'See https://cdn.example.net/media/clip.png for details.'}</FormatNewlines>,
    );

    // ASSERT
    const linkEl = screen.getByRole('link', { name: 'https://cdn.example.net/media/clip.png' });
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('redirect'));
    expect(linkEl).toHaveAttribute('rel', 'nofollow noopener');
    expect(route).toHaveBeenCalledWith('redirect', {
      url: 'https://cdn.example.net/media/clip.png',
    });
    expect(linkEl.parentElement).toHaveTextContent(
      'See https://cdn.example.net/media/clip.png for details.',
    );
  });

  it('given a RetroAchievements URL, uses a direct link', () => {
    // ARRANGE
    render(<FormatNewlines>{'Check https://retroachievements.org/game/9 first.'}</FormatNewlines>);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: 'https://retroachievements.org/game/9' });
    expect(linkEl).toHaveAttribute('href', 'https://retroachievements.org/game/9');
    expect(linkEl).not.toHaveAttribute('rel');
  });
});
