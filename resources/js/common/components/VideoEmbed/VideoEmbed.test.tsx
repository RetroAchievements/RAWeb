import { render, screen } from '@/test';

import { VideoEmbed } from './VideoEmbed';

describe('Component: VideoEmbed', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<VideoEmbed src="https://youtube.com/watch?v=123" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an unrecognized URL, renders a fallback link', () => {
    // ARRANGE
    render(<VideoEmbed src="https://ibb.co/9gShSmF" />);

    // ASSERT
    expect(screen.queryByTestId('video-embed')).not.toBeInTheDocument();

    const linkEl = screen.getByRole('link');
    expect(linkEl).toHaveAttribute('href', 'https://ibb.co/9gShSmF');
    expect(linkEl).toHaveAttribute('target', '_blank');
  });

  it('given a YouTube URL, renders a YouTube embed iframe', () => {
    // ARRANGE
    render(<VideoEmbed src="https://youtube.com/watch?v=dQw4w9WgXcQ" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();
    expect(iframeEl.getAttribute('src')).toEqual('//www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
  });

  it('given a YouTube URL with params, preserves them in the embed URL', () => {
    // ARRANGE
    render(<VideoEmbed src="https://youtube.com/watch?v=dQw4w9WgXcQ&t=123" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl.getAttribute('src')).toEqual(
      '//www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=123',
    );
  });

  it('given a Twitch video URL, renders a Twitch video embed iframe', () => {
    // ARRANGE
    render(<VideoEmbed src="https://twitch.tv/videos/123456789" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();

    const src = iframeEl.getAttribute('src');
    expect(src).toContain('//player.twitch.tv/?');
    expect(src).toContain('video=123456789');
    expect(src).toContain('autoplay=false');
    expect(src).toMatch(/parent=[^&]+/);
  });

  it('given a Twitch collection URL, renders a Twitch collection embed iframe', () => {
    // ARRANGE
    render(<VideoEmbed src="https://twitch.tv/collections/abc123" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();

    const src = iframeEl.getAttribute('src');
    expect(src).toContain('//player.twitch.tv/?');
    expect(src).toContain('collection=abc123');
    expect(src).toContain('autoplay=false');
    expect(src).toMatch(/parent=[^&]+/);
  });

  it('given a Twitch clip URL, renders a Twitch clip embed iframe', () => {
    // ARRANGE
    render(<VideoEmbed src="https://clips.twitch.tv/ClipName" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();

    const src = iframeEl.getAttribute('src');
    expect(src).toContain('//clips.twitch.tv/embed?');
    expect(src).toContain('clip=ClipName');
    expect(src).toContain('autoplay=false');
    expect(src).toMatch(/parent=[^&]+/);
  });
});
