import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createGameSet,
  createRaEvent,
  createUser,
} from '@/test/factories';

import { BaseCommand } from '../../../+vendor/BaseCommand';
import { SearchResults } from './SearchResults';

// JSDOM doesn't have manual navigation changes implemented.
console.error = vi.fn();

describe('Component: SearchResults', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {},
      query: 'test',
      scopes: ['users', 'games', 'hubs', 'achievements'],
      scopeRelevance: { users: 0, games: 0, hubs: 0, achievements: 0 },
    };

    const { container } = render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no search results are provided, displays nothing', () => {
    // ARRANGE
    render(
      <BaseCommand>
        <SearchResults currentSearchMode="all" searchResults={undefined} onClose={vi.fn()} />
      </BaseCommand>,
    );

    // ASSERT
    expect(screen.queryByTestId('search-results')).not.toBeInTheDocument();
  });

  it('given search results with users, displays the users section', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [createUser({ displayName: 'TestUser' })],
        games: [],
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users'],
      scopeRelevance: { users: 0.5, games: 0, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    expect(screen.getByText(/users/i)).toBeVisible();
    expect(screen.getByText('TestUser')).toBeVisible();
  });

  it('given search results with games, displays the games section', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [],
        games: [createGame({ title: 'Super Mario Bros.' })],
        hubs: [],
        achievements: [],
      },
      query: 'mario',
      scopes: ['games'],
      scopeRelevance: { users: 0, games: 0.8, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    expect(screen.getByText(/games/i)).toBeVisible();
    expect(screen.getByText(/super mario bros/i)).toBeVisible();
  });

  it('given empty sections, does not display those sections', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [createUser()],
        games: [],
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users', 'games', 'hubs', 'achievements'],
      scopeRelevance: { users: 0.5, games: 0, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    expect(screen.getByText(/users/i)).toBeVisible();
    expect(screen.queryByText(/games/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/hubs/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/achievements/i)).not.toBeInTheDocument();
  });

  it('given search mode is "all", limits results per section', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [
          createUser({ id: 1 }),
          createUser({ id: 2 }),
          createUser({ id: 3 }),
          createUser({ id: 4 }),
        ],
        games: Array.from({ length: 8 }, (_, i) => createGame({ id: i + 1 })),
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users', 'games'],
      scopeRelevance: { users: 0.5, games: 0.5, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    // ... users limit is 3 in "all" mode ...
    expect(screen.getByText(/3 results/i)).toBeVisible();
    // ... games limit is 6 in "all" mode ...
    expect(screen.getByText(/6 results/i)).toBeVisible();
  });

  it('given search mode is not "all", shows up to 10 results per section', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: Array.from({ length: 12 }, (_, i) => createUser({ id: i + 1 })),
        games: [],
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users'],
      scopeRelevance: { users: 0.5, games: 0, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="users"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    expect(screen.getByText(/10 results/i)).toBeVisible();
  });

  it('given sections with significantly different relevance scores, sorts by relevance', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [createUser()],
        games: [createGame()],
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users', 'games'],
      scopeRelevance: { users: 0.9, games: 0.2, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    // ... users should come first due to higher relevance ...
    const sectionTexts = screen.getAllByTestId('search-results');
    expect(sectionTexts[0]).toHaveTextContent(/users/i);
    expect(sectionTexts[1]).toHaveTextContent(/games/i);
  });

  it('given games have significantly higher relevance than users, games come first', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [createUser()],
        games: [createGame()],
        hubs: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['users', 'games'],
      scopeRelevance: { users: 0.2, games: 0.9, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    const sectionTexts = screen.getAllByTestId('search-results');
    expect(sectionTexts[0]).toHaveTextContent(/games/i);
    expect(sectionTexts[1]).toHaveTextContent(/users/i);
  });

  it('given sections with similar relevance scores, uses default ordering', () => {
    // ARRANGE
    const mockSearchResults = {
      results: {
        users: [createUser()],
        games: [createGame()],
        hubs: [createGameSet()],
        events: [createRaEvent({ state: 'active' })],
        achievements: [createAchievement()],
      },
      query: 'test',
      scopes: ['users', 'games', 'hubs', 'events', 'achievements'],
      scopeRelevance: { users: 0.5, games: 0.5, hubs: 0.5, events: 0.5, achievements: 0.5 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={vi.fn()}
        />
      </BaseCommand>,
    );

    // ASSERT
    // ... default order: games, hubs, users, events, achievements ...
    const sectionTexts = screen.getAllByTestId('search-results');
    expect(sectionTexts[0]).toHaveTextContent(/games/i);
    expect(sectionTexts[1]).toHaveTextContent(/hubs/i);
    expect(sectionTexts[2]).toHaveTextContent(/users/i);
    expect(sectionTexts[3]).toHaveTextContent(/events/i);
    expect(sectionTexts[4]).toHaveTextContent(/achievements/i);
  });

  it('given the user clicks on a user result, navigates to user page and closes', async () => {
    // ARRANGE
    const mockOnClose = vi.fn();
    const mockSearchResults = {
      results: {
        users: [createUser({ displayName: 'JohnDoe' })],
        games: [],
        hubs: [],
        events: [],
        achievements: [],
      },
      query: 'john',
      scopes: ['users'],
      scopeRelevance: { users: 0.5, games: 0, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={mockOnClose}
        />
      </BaseCommand>,
    );

    // ACT
    await userEvent.click(screen.getByText('JohnDoe'));

    // ASSERT
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('given the user clicks on a game result, navigates to game page and closes', async () => {
    // ARRANGE
    const mockOnClose = vi.fn();
    const mockSearchResults = {
      results: {
        users: [],
        games: [createGame({ id: 123, title: 'Test Game' })],
        hubs: [],
        events: [],
        achievements: [],
      },
      query: 'test',
      scopes: ['games'],
      scopeRelevance: { users: 0, games: 0.5, hubs: 0, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={mockOnClose}
        />
      </BaseCommand>,
    );

    // ACT
    await userEvent.click(screen.getByText(/test game/i));

    // ASSERT
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('given the user clicks on a hub result, navigates to hub page and closes', async () => {
    // ARRANGE
    const mockOnClose = vi.fn();
    const mockSearchResults = {
      results: {
        users: [],
        games: [],
        events: [],
        hubs: [createGameSet({ id: 456, title: 'Mario Series' })],
        achievements: [],
      },
      query: 'mario',
      scopes: ['hubs'],
      scopeRelevance: { users: 0, games: 0, hubs: 0.5, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={mockOnClose}
        />
      </BaseCommand>,
    );

    // ACT
    await userEvent.click(screen.getByText(/mario series/i));

    // ASSERT
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('given the user clicks on an achievement result, navigates to achievement page and closes', async () => {
    // ARRANGE
    const mockOnClose = vi.fn();
    const mockSearchResults = {
      results: {
        users: [],
        games: [],
        hubs: [],
        events: [],
        achievements: [createAchievement({ id: 789, title: 'First Blood' })],
      },
      query: 'first',
      scopes: ['achievements'],
      scopeRelevance: { users: 0, games: 0, hubs: 0, achievements: 0.5 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={mockOnClose}
        />
      </BaseCommand>,
    );

    // ACT
    await userEvent.click(screen.getByText(/first blood/i));

    // ASSERT
    expect(mockOnClose).toHaveBeenCalled();
  });

  it('given the user clicks on an event result, navigates to event page and closes', async () => {
    // ARRANGE
    const mockOnClose = vi.fn();
    const mockSearchResults = {
      results: {
        users: [],
        games: [],
        hubs: [],
        achievements: [],
        events: [
          createRaEvent({
            state: 'active',
            legacyGame: createGame({ title: 'Achievement of the Week' }),
          }),
        ],
      },
      query: 'first',
      scopes: ['events'],
      scopeRelevance: { users: 0, games: 0, hubs: 0, events: 0.5, achievements: 0 },
    };

    render(
      <BaseCommand>
        <SearchResults
          currentSearchMode="all"
          searchResults={mockSearchResults as any}
          onClose={mockOnClose}
        />
      </BaseCommand>,
    );

    // ACT
    await userEvent.click(screen.getByText(/achievement of the week/i));

    // ASSERT
    expect(mockOnClose).toHaveBeenCalled();
  });
});
