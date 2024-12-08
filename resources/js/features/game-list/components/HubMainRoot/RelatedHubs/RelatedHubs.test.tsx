import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { RelatedHubs } from './RelatedHubs';

describe('Component: RelatedHubs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.HubPageProps>(<RelatedHubs />, {
      pageProps: {
        relatedHubs: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given related hubs are present, displays them in a table', () => {
    // ARRANGE
    const hubs = [createGameSet(), createGameSet()];

    render<App.Platform.Data.HubPageProps>(<RelatedHubs />, {
      pageProps: { relatedHubs: hubs },
    });

    // ASSERT
    expect(screen.getByText(/related hubs/i)).toBeVisible();

    expect(screen.getByText(hubs[0].title!)).toBeVisible();
    expect(screen.getByText(hubs[1].title!)).toBeVisible();
  });

  it('given no related hubs exist, does not render a table and displays an empty state', () => {
    // ARRANGE
    render<App.Platform.Data.HubPageProps>(<RelatedHubs />, {
      pageProps: {
        relatedHubs: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('table')).not.toBeInTheDocument();

    expect(screen.getByText(/no related hubs/i)).toBeVisible();
  });

  it('given a related hub, displays its game and link counts', () => {
    // ARRANGE
    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<RelatedHubs />, {
      pageProps: { relatedHubs: [hub] },
    });

    // ASSERT
    expect(screen.getByText(hub.gameCount.toString())).toBeVisible();
    expect(screen.getByText(hub.linkCount.toString())).toBeVisible();
  });

  it('given a related hub has a badge URL, renders the image with correct attributes', () => {
    // ARRANGE
    const hub = createGameSet();

    render<App.Platform.Data.HubPageProps>(<RelatedHubs />, {
      pageProps: { relatedHubs: [hub] },
    });

    // ASSERT
    const imgEl = screen.getByAltText(hub.title!);

    expect(imgEl).toBeVisible();
    expect(imgEl.getAttribute('src')).toEqual(hub.badgeUrl);
    expect(imgEl.getAttribute('loading')).toEqual('lazy');
    expect(imgEl.getAttribute('decoding')).toEqual('async');
  });

  it('cleans hub titles', () => {
    // ARRANGE
    const hub = createGameSet();
    hub.title = '[Series - Sonic the Hedgehog]';

    render(<RelatedHubs />, {
      pageProps: { relatedHubs: [hub] },
    });

    // ASSERT
    expect(screen.getByText(/series - sonic the hedgehog/i)).toBeVisible();
    expect(screen.queryByText(/\[series/i)).not.toBeInTheDocument();
  });
});
