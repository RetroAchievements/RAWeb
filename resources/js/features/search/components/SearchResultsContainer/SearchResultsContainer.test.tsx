import { render, screen } from '@/test';
import {
  createAchievement,
  createComment,
  createForumTopicComment,
  createGame,
  createGameSet,
  createRaEvent,
  createUser,
} from '@/test/factories';

import { SearchResultsContainer } from './SearchResultsContainer';

describe('Component: SearchResultsContainer', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SearchResultsContainer isLoading={false} query="test" searchResults={undefined} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the query is less than 3 characters, shows a prompt to enter a search term', () => {
    // ARRANGE
    render(<SearchResultsContainer isLoading={false} query="ab" searchResults={undefined} />);

    // ASSERT
    expect(screen.getByText(/enter a search term to get started/i)).toBeVisible();
  });

  it('given isLoading is true, shows loading skeletons', () => {
    // ARRANGE
    render(<SearchResultsContainer isLoading={true} query="test" searchResults={undefined} />);

    // ASSERT
    const skeletons = document.querySelectorAll('.animate-pulse');
    expect(skeletons.length).toEqual(10);
  });

  it('given no results are found, shows a no results message', () => {
    // ARRANGE
    const emptyResults = {
      results: {},
      query: 'test',
      scopes: ['users' as const],
      scopeRelevance: {},
    };

    render(<SearchResultsContainer isLoading={false} query="test" searchResults={emptyResults} />);

    // ASSERT
    expect(screen.getByText(/no results found/i)).toBeVisible();
  });

  it('given searchResults is undefined and the query is valid, does not render results content', () => {
    // ARRANGE
    render(<SearchResultsContainer isLoading={false} query="test" searchResults={undefined} />);

    // ASSERT
    expect(screen.queryByText(/users/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/games/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/no results found/i)).not.toBeInTheDocument();
  });

  it('given users are in the results, renders the users section', () => {
    // ARRANGE
    const mockUser = createUser({ displayName: 'TestUser123' });
    const searchResults = {
      results: { users: [mockUser] },
      query: 'test',
      scopes: ['users' as const],
      scopeRelevance: { users: 1 },
    };

    render(<SearchResultsContainer isLoading={false} query="test" searchResults={searchResults} />);

    // ASSERT
    expect(screen.getByText(/users/i)).toBeVisible();
    expect(screen.getByText(/testuser123/i)).toBeVisible();
  });

  it('given games are in the results, renders the games section', () => {
    // ARRANGE
    const mockGame = createGame({ title: 'Super Mario Bros.' });
    const searchResults = {
      results: { games: [mockGame] },
      query: 'mario',
      scopes: ['games' as const],
      scopeRelevance: { games: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="mario" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/games/i)).toBeVisible();
    expect(screen.getByText(/super mario bros/i)).toBeVisible();
  });

  it('given hubs are in the results, renders the hubs section', () => {
    // ARRANGE
    const mockHub = createGameSet({ title: 'Mario Series' });
    const searchResults = {
      results: { hubs: [mockHub] },
      query: 'mario',
      scopes: ['hubs' as const],
      scopeRelevance: { hubs: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="mario" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/hubs/i)).toBeVisible();
    expect(screen.getByText(/mario series/i)).toBeVisible();
  });

  it('given achievements are in the results, renders the achievements section', () => {
    // ARRANGE
    const mockAchievement = createAchievement({ title: 'Beat Level 1' });
    const searchResults = {
      results: { achievements: [mockAchievement] },
      query: 'level',
      scopes: ['achievements' as const],
      scopeRelevance: { achievements: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="level" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/achievements/i)).toBeVisible();
    expect(screen.getByText(/beat level 1/i)).toBeVisible();
  });

  it('given events are in the results, renders the events section', () => {
    // ARRANGE
    const mockEvent = createRaEvent({ legacyGame: createGame({ title: 'Event Game' }) });
    const searchResults = {
      results: { events: [mockEvent] },
      query: 'event',
      scopes: ['events' as const],
      scopeRelevance: { events: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="event" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/events/i)).toBeVisible();
    expect(screen.getByText(/event game/i)).toBeVisible();
  });

  it('given forum posts are in the results, renders the forum posts section', () => {
    // ARRANGE
    const mockForumComment = createForumTopicComment({ body: 'This is a forum post' });
    const searchResults = {
      results: { forum_comments: [mockForumComment] },
      query: 'forum',
      scopes: ['forum_comments' as const],
      scopeRelevance: { forum_comments: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="forum" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/forum posts/i)).toBeVisible();
    expect(screen.getByText(/this is a forum post/i)).toBeVisible();
  });

  it('given comments are in the results, renders the comments section', () => {
    // ARRANGE
    const mockComment = createComment({ payload: 'This is a comment' });
    const searchResults = {
      results: { comments: [mockComment] },
      query: 'comment',
      scopes: ['comments' as const],
      scopeRelevance: { comments: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="comment" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/comments/i)).toBeVisible();
    expect(screen.getByText(/this is a comment/i)).toBeVisible();
  });

  it('given multiple result types are in the results, renders all relevant sections', () => {
    // ARRANGE
    const searchResults = {
      results: {
        users: [createUser({ displayName: 'TestUser' })],
        games: [createGame({ title: 'Super Mario World' })],
      },
      query: 'mario',
      scopes: ['users' as const, 'games' as const],
      scopeRelevance: { users: 0.8, games: 1 },
    };

    render(
      <SearchResultsContainer isLoading={false} query="mario" searchResults={searchResults} />,
    );

    // ASSERT
    expect(screen.getByText(/users/i)).toBeVisible();
    expect(screen.getByText(/games/i)).toBeVisible();
    expect(screen.getByText(/testuser/i)).toBeVisible();
    expect(screen.getByText(/super mario world/i)).toBeVisible();
  });
});
