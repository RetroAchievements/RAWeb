import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { useEffect } from 'react';

import { render, screen, waitFor } from '@/test';
import { createGame, createUser } from '@/test/factories';

import { GlobalSearch } from './GlobalSearch';

// Suppress "Error: AggregateError" noise from mocking axios.
console.error = vi.fn();

vi.mock('./hooks/useGlobalSearchDebounce', () => ({
  useGlobalSearchDebounce: vi.fn(({ rawQuery, setSearchTerm }) => {
    // Use useEffect to avoid calling setSearchTerm during render.
    useEffect(() => {
      if (rawQuery.length >= 3) {
        setSearchTerm(rawQuery);
      } else if (rawQuery.length === 0) {
        setSearchTerm('');
      }
    }, [rawQuery, setSearchTerm]);
  }),
}));

vi.mock('./hooks/useGlobalSearchHotkey', () => ({
  useGlobalSearchHotkey: vi.fn(),
}));

vi.mock('./hooks/useScrollToTopOnSearchResults', () => ({
  useScrollToTopOnSearchResults: vi.fn(() => ({ current: null })),
}));

describe('Component: GlobalSearch', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given isOpen is true, displays the dialog', () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given isOpen is false, does not display the dialog', () => {
    // ARRANGE
    render(<GlobalSearch isOpen={false} onOpenChange={vi.fn()} />);

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('displays the search input with placeholder text', async () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    await waitFor(() => expect(screen.getAllByPlaceholderText(/search/i)[0]).toBeVisible());
  });

  it('displays initial state when no search has been performed', async () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    await waitFor(() => {
      expect(
        screen.getByText(/search for games, hubs, users, events, and achievements/i),
      ).toBeVisible();
    });
    expect(screen.getByText(/type at least 3 characters to begin/i)).toBeVisible();
  });

  it('given the user types a search query, displays search results', async () => {
    // ARRANGE
    const mockSearchResponse = {
      data: {
        results: {
          users: [createUser({ displayName: 'TestUser' })],
          games: [createGame({ title: 'Super Mario Bros.' })],
          hubs: [],
          achievements: [],
        },
        query: 'test',
        scopes: ['users', 'games', 'hubs', 'achievements'],
        scopeRelevance: { users: 0.5, games: 0.8, hubs: 0, achievements: 0 },
      },
    };

    vi.spyOn(axios, 'get').mockResolvedValueOnce(mockSearchResponse);

    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText('TestUser')).toBeVisible();
    });

    expect(screen.getByText(/super mario bros/i)).toBeVisible();
  });

  it('displays a no results message when search returns empty', async () => {
    // ARRANGE
    const mockSearchResponse = {
      data: {
        results: {
          users: [],
          games: [],
          hubs: [],
          events: [],
          achievements: [],
        },
        query: 'xyz',
        scopes: ['users', 'games', 'hubs', 'events', 'achievements'],
        scopeRelevance: { users: 0, games: 0, hubs: 0, events: 0, achievements: 0 },
      },
    };

    vi.spyOn(axios, 'get').mockResolvedValueOnce(mockSearchResponse);

    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[1], 'xyz');

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/no results found/i)).toBeVisible();
    });
  });

  it('given the user changes search mode, updates the search scope', async () => {
    // ARRANGE
    const axiosSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        results: { games: [createGame()] },
        query: 'test',
        scopes: ['games'],
        scopeRelevance: { games: 1 },
      },
    });

    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /games/i }));
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    // ASSERT
    await waitFor(() => {
      expect(axiosSpy).toHaveBeenCalledWith(expect.stringContaining('scope=games'));
    });
  });

  it('given the dialog is closed, clears the search query', async () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    const { rerender } = render(<GlobalSearch isOpen={true} onOpenChange={mockOnOpenChange} />);

    // ... populate the search field ...
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    // ... simulate closing the dialog ...
    const closeButton = screen.getByRole('button', { name: /close/i });
    await userEvent.click(closeButton);

    // ACT
    // ... simulate reopening the dialog ...
    rerender(<GlobalSearch isOpen={true} onOpenChange={mockOnOpenChange} />);

    // ASSERT
    expect(screen.getAllByPlaceholderText(/search/i)[0]).toHaveValue('');
  });

  it('displays the browse link with search query', async () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'mario');

    // ASSERT
    const [browseLink] = await screen.findAllByRole('link', { name: /browse/i });
    expect(browseLink).toHaveAttribute('href', '/searchresults.php?s=mario');
  });

  it('displays the browse link without query when search is empty', async () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    const [browseLink] = await screen.findAllByRole('link', { name: /browse/i });
    expect(browseLink).toHaveAttribute('href', '/searchresults.php');
  });

  it('given search mode is "all", searches all scopes', async () => {
    // ARRANGE
    const axiosSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        results: {},
        query: 'test',
        scopes: ['users', 'games', 'hubs', 'events', 'achievements'],
        scopeRelevance: {},
      },
    });

    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    // ASSERT
    await waitFor(() => {
      expect(axiosSpy).toHaveBeenCalledWith(
        expect.stringContaining('scope=users%2Cgames%2Chubs%2Cevents%2Cachievements'),
      );
    });
  });

  it('uses the hardcoded route for Blade contexts', async () => {
    // ARRANGE
    const axiosSpy = vi.spyOn(axios, 'get').mockResolvedValue({
      data: {
        results: {},
        query: 'test',
        scopes: ['users', 'games', 'hubs', 'achievements'],
        scopeRelevance: {},
      },
    });

    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    // ASSERT
    await waitFor(() => {
      expect(axiosSpy).toHaveBeenCalledWith(expect.stringContaining('/internal-api/search'));
    });
  });

  it('given the user clicks on a search result, closes the dialog', async () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    const mockSearchResponse = {
      data: {
        results: {
          users: [createUser({ displayName: 'TestUser' })],
          games: [],
          hubs: [],
          achievements: [],
        },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 0.5 },
      },
    };

    vi.spyOn(axios, 'get').mockResolvedValueOnce(mockSearchResponse);

    render(<GlobalSearch isOpen={true} onOpenChange={mockOnOpenChange} />);

    // ACT
    await userEvent.type(screen.getAllByPlaceholderText(/search/i)[0], 'test');

    await waitFor(() => {
      expect(screen.getByText('TestUser')).toBeVisible();
    });

    await userEvent.click(screen.getByText('TestUser'));

    // ASSERT
    expect(mockOnOpenChange).toHaveBeenCalledWith(false);
  });

  it('given the user presses Space while typing, inserts a space character without scrolling', async () => {
    // ARRANGE
    render(<GlobalSearch isOpen={true} onOpenChange={vi.fn()} />);

    const searchInput = screen.getAllByPlaceholderText(/search/i)[0];

    // ACT
    await userEvent.type(searchInput, 'hello');
    await userEvent.keyboard(' ');
    await userEvent.type(searchInput, 'world');

    // ASSERT
    expect(searchInput).toHaveValue('hello world');
  });
});
