import { render, screen } from '@/test';

import { ShortcodeVideo } from './ShortcodeVideo';

describe('Component: ShortcodeVideo', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeVideo src="https://youtube.com/watch?v=123" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an invalid video URL, renders nothing', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://invalid-url.com" />);

    // ASSERT
    expect(screen.queryByTestId('video-embed')).not.toBeInTheDocument();
  });

  it('given a YouTube URL, renders a YouTube embed iframe', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://youtube.com/watch?v=dQw4w9WgXcQ" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();
    expect(iframeEl.getAttribute('src')).toEqual('//www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
  });

  it('given a YouTube URL with params, preserves them in the embed URL', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://youtube.com/watch?v=dQw4w9WgXcQ&t=123" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl.getAttribute('src')).toEqual(
      '//www.youtube-nocookie.com/embed/dQw4w9WgXcQ?start=123',
    );
  });

  it('given a Twitch video URL, renders a Twitch video embed iframe', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://twitch.tv/videos/123456789" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();
    expect(iframeEl.getAttribute('src')).toEqual(
      '//player.twitch.tv/?video=123456789&parent=localhost&autoplay=false',
    );
  });

  it('given a Twitch collection URL, renders a Twitch collection embed iframe', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://twitch.tv/collections/abc123" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();
    expect(iframeEl.getAttribute('src')).toEqual(
      '//player.twitch.tv/?collection=abc123&parent=localhost&autoplay=false',
    );
  });

  it('given a Twitch clip URL, renders a Twitch clip embed iframe', () => {
    // ARRANGE
    render(<ShortcodeVideo src="https://clips.twitch.tv/ClipName" />);

    // ASSERT
    const iframeEl = screen.getByTestId('video-embed-iframe');
    expect(iframeEl).toBeVisible();
    expect(iframeEl.getAttribute('src')).toEqual(
      '//clips.twitch.tv/embed?clip=ClipName&parent=localhost&autoplay=false',
    );
  });
});
