import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { HubHeading } from './HubHeading';

describe('Component: HubHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', badgeUrl: 'https://example.com/badge.png' });

    const { container } = render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: {} },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a hub with a badge URL, displays the badge image', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', badgeUrl: 'https://example.com/badge.png' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: {} },
    });

    // ASSERT
    const imgEl = screen.getByRole('img', { name: hub.title! });

    expect(imgEl).toBeVisible();
    expect(imgEl).toHaveAttribute('src', hub.badgeUrl);
  });

  it('given a hub without a badge URL, does not display a badge image', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', badgeUrl: null });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: {} },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: hub.title! })).not.toBeInTheDocument();
  });

  it('given a hub title, displays the cleaned hub title', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', title: '[Series - Sonic the Hedgehog]' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: {} },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
      /series - sonic the hedgehog/i,
    );

    expect(screen.queryByText(/\[/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/\]/i)).not.toBeInTheDocument();
  });

  it('given the user cannot manage game sets and there is no forum topic, does not show any action buttons', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: { manageGameSets: false } },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /view forum topic/i })).not.toBeInTheDocument();
  });

  it('given there is a forum topic ID, shows the View Forum Topic button regardless of permissions', () => {
    // ARRANGE
    const hub = createGameSet({
      type: 'hub',
      forumTopicId: 12345,
    });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: { manageGameSets: false } },
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /view forum topic/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('forum-topic.show'));
  });

  it('given the user can manage hubs, shows a Manage button', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', title: '[Series - Sonic the Hedgehog]' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: { manageGameSets: true } },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
  });

  it('given the user can manage hubs and there is no associated forum topic, does not show a View Forum Topic button', () => {
    // ARRANGE
    const hub = createGameSet({ type: 'hub', title: '[Series - Sonic the Hedgehog]' });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: { manageGameSets: true } },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /view forum topic/i })).not.toBeInTheDocument();
  });

  it('given the user can manage hubs and there is an associated forum topic, shows the View Forum Topic button', () => {
    // ARRANGE
    const hub = createGameSet({
      type: 'hub',
      title: '[Series - Sonic the Hedgehog]',
      forumTopicId: 12345, // !!
    });

    render<App.Platform.Data.HubPageProps>(<HubHeading />, {
      pageProps: { hub, can: { manageGameSets: true } },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /view forum topic/i })).toBeVisible();
  });
});
