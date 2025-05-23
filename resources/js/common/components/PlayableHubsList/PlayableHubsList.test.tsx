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
    const mockHubs = [createGameSet({ title: 'AAAAA' }), createGameSet({ title: 'BBBBB' })];

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
    const mockHubs = [createGameSet({ title: '[Events - Achievement of the Week]' })];

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
      createGameSet({ title: 'ZZZ' }),
      createGameSet({ title: 'AAA' }),
      createGameSet({ title: 'CCC' }),
      createGameSet({ title: 'BBB' }),
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
      createGameSet({ title: 'ZZZ', id: 1 }),
      createGameSet({ title: 'AAA', id: 2 }), // !! only this one should be visible
      createGameSet({ title: 'CCC', id: 3 }),
      createGameSet({ title: 'BBB', id: 4 }),
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
});
