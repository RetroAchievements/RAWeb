import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { PlayableHubsList } from './PlayableHubsList';

describe('Component: HubsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayableHubsList hubs={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no hubs, renders nothing', () => {
    // ARRANGE
    render(<PlayableHubsList hubs={[]} />);

    // ASSERT
    expect(screen.queryByTestId('hubs-list')).not.toBeInTheDocument();
  });

  it('given there are hubs, renders the hubs list with the correct title', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: 'Meta - AAAAA' }),
      createGameSet({ title: 'Meta - BBBBB' }),
    ];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.getByTestId('hubs-list')).toBeVisible();
    expect(screen.getByText(/hubs/i)).toBeVisible();

    expect(screen.getAllByRole('listitem')).toHaveLength(2);
    expect(screen.getByText(/aaaaa/i)).toBeVisible();
    expect(screen.getByText(/bbbbb/i)).toBeVisible();
  });

  it('cleans hub titles', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: '[Events - Achievement of the Week]', isEventHub: true }),
    ];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/\[/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/\]/i)).not.toBeInTheDocument();
    expect(screen.getByText('Events - Achievement of the Week')).toBeVisible();
  });

  it('sorts hub titles alphabetically', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: 'Meta - ZZZ' }),
      createGameSet({ title: 'Meta - AAA' }),
      createGameSet({ title: 'Meta - CCC' }),
      createGameSet({ title: 'Meta - BBB' }),
    ];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    const links = screen.getAllByRole('link');

    expect(links[0]).toHaveTextContent(/aaa/i);
    expect(links[1]).toHaveTextContent(/bbb/i);
    expect(links[2]).toHaveTextContent(/ccc/i);
    expect(links[3]).toHaveTextContent(/zzz/i);
  });

  it('given the user cannot manage games, does not display meta team hubs', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: 'Meta|QA - Noncompliant Writing' })];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false, // !!
        },
      },
    });

    // ASSERT
    const links = screen.queryAllByRole('link');
    expect(links.length).toEqual(0);
  });

  it('given the user can manage games, displays meta team hubs', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: 'Meta|QA - Noncompliant Writing' })];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: true, // !!
        },
      },
    });

    // ASSERT
    const links = screen.getAllByRole('link');
    expect(links.length).toEqual(1);
  });

  it('given some hub IDs are marked for exclusion, does not display their links', () => {
    const mockHubs = [
      createGameSet({ title: 'Meta - ZZZ', id: 1 }),
      createGameSet({ title: 'Meta - AAA', id: 2 }), // !! only this one should be visible
      createGameSet({ title: 'Meta - CCC', id: 3 }),
      createGameSet({ title: 'Meta - BBB', id: 4 }),
    ];

    render(<PlayableHubsList hubs={mockHubs} excludeHubIds={[1, 3, 4]} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /zzz/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /ccc/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /bbb/i })).not.toBeInTheDocument();

    expect(screen.getByRole('link', { name: /aaa/i })).toBeVisible();
  });

  it('given the user cannot manage games but the hub is Meta|Art, displays the Meta|Art hub', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: 'Meta|Art - Stock/Recycled Badges' }),
      createGameSet({ title: 'Meta|QA - Missing Content' }),
    ];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false, // !!
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /stock\/recycled badges/i })).toBeVisible(); // Meta|Art is visible
    expect(screen.queryByRole('link', { name: /missing content/i })).not.toBeInTheDocument(); // Meta|QA is hidden
  });

  it('enforces visibility rules', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: '[License - Nintendo]', isEventHub: false }), // Not Meta, Event, Achievement Extras, or Series
      createGameSet({ title: 'Meta - Language - English', isEventHub: false }), // Meta hub
      createGameSet({ title: 'Event Hub 2023', isEventHub: true }), // Event hub
      createGameSet({ title: 'RANews - 2023-01', isEventHub: false }), // Achievement Extras hub
      createGameSet({ title: '[Series - Mario]', isEventHub: false }), // Series hub
      createGameSet({ title: '[Subseries - Wario]', isEventHub: false }), // Subseries hub
    ];

    render(<PlayableHubsList hubs={mockHubs} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /nintendo/i })).not.toBeInTheDocument(); // License hub hidden
    expect(screen.getByRole('link', { name: /english/i })).toBeVisible(); // Meta hub visible
    expect(screen.getByRole('link', { name: /event hub 2023/i })).toBeVisible(); // Event hub visible
    expect(screen.getByRole('link', { name: /2023-01/i })).toBeVisible(); // Achievement Extras hub visible
    expect(screen.getByRole('link', { name: /mario/i })).toBeVisible(); // Series hub visible
    expect(screen.getByRole('link', { name: /wario/i })).toBeVisible(); // Subseries hub visible
  });

  it('given a game has multiple series hubs, displays non-prioritized series hubs when the prioritized one is excluded', () => {
    // ARRANGE
    const mockHubs = [
      createGameSet({ title: '[Series - Mario]', id: 1 }),
      createGameSet({ title: '[Subseries - Wario]', id: 2 }), // !! this one is prioritized/excluded
      createGameSet({ title: 'Meta - Test Hub', id: 3 }),
    ];

    render(<PlayableHubsList hubs={mockHubs} excludeHubIds={[2]} />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /mario/i })).toBeVisible(); // Non-prioritized series hub visible
    expect(screen.queryByRole('link', { name: /wario/i })).not.toBeInTheDocument(); // Prioritized series hub excluded
    expect(screen.getByRole('link', { name: /test hub/i })).toBeVisible(); // Other hubs still visible
  });

  it('given the variant is game, displays Additional Hubs heading with an info icon', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: 'Meta - Test Hub' })];

    render(<PlayableHubsList hubs={mockHubs} variant="game" />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /additional hubs/i })).toBeVisible();
  });

  it('given the variant is event, displays the normal Hubs heading', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: 'Meta - Test Hub' })];

    render(<PlayableHubsList hubs={mockHubs} variant="event" />, {
      pageProps: {
        can: {
          manageGames: false,
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Hubs' })).toBeVisible();
    expect(screen.queryByText(/additional/i)).not.toBeInTheDocument();
  });
});
