import userEvent from '@testing-library/user-event';

import type { SearchResults } from '@/common/hooks/queries/useSearchQuery';
import { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';
import { render, screen } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { SearchMainRoot } from './SearchMainRoot';

vi.mock('@/common/hooks/queries/useSearchQuery');

describe('Component: SearchMainRoot', () => {
  beforeEach(() => {
    vi.mocked(useSearchQuery).mockReturnValue({
      data: undefined,
      isLoading: false,
      setSearchTerm: vi.fn(),
      setShouldUsePlaceholderData: vi.fn(),
    } as unknown as ReturnType<typeof useSearchQuery>);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible page heading', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /search/i, level: 1 })).toBeVisible();
  });

  it('displays the search input', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ASSERT
    expect(screen.getAllByRole('textbox')[0]).toBeVisible();
  });

  it('displays the scope selector with all scope buttons', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /all/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /games/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /users/i })).toBeVisible();
  });

  it('given an initial query, populates the search input', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: 'mario',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ASSERT
    expect(screen.getAllByRole('textbox')[0]).toHaveValue('mario');
  });

  it('given an initial scope, selects that scope', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'games',
        initialPage: 1,
      },
    });

    // ASSERT
    const gamesButton = screen.getByRole('button', { name: /games/i });
    expect(gamesButton).toBePressed();
  });

  it('given an invalid initial scope, defaults to all', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'invalid_scope',
        initialPage: 1,
      },
    });

    // ASSERT
    const allButton = screen.getByRole('button', { name: /all/i });
    expect(allButton).toBePressed();
  });

  it('given the user types in the search input, updates the query', async () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ACT
    const input = screen.getAllByRole('textbox')[0];
    await userEvent.type(input, 'zelda');

    // ASSERT
    expect(input).toHaveValue('zelda');
  });

  it('given the user clicks a scope button, changes the selected scope', async () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /users/i }));

    // ASSERT
    const usersButton = screen.getByRole('button', { name: /users/i });
    expect(usersButton).toBePressed();
  });

  it('given there is no query, displays the enter search term message', () => {
    // ARRANGE
    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: '',
        initialScope: 'all',
        initialPage: 1,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/enter a search term to get started/i)).toBeVisible();
  });

  it('given a non-all scope with pagination results, displays pagination controls', () => {
    // ARRANGE
    const mockSearchResults: SearchResults = {
      results: {
        games: [createGame()],
      },
      query: 'mario',
      scopes: ['games'],
      scopeRelevance: { games: 100 },
      pagination: {
        currentPage: 1,
        lastPage: 5,
        perPage: 50,
        total: 250,
      },
    };

    vi.mocked(useSearchQuery).mockReturnValue({
      data: mockSearchResults,
      isLoading: false,
      setSearchTerm: vi.fn(),
      setShouldUsePlaceholderData: vi.fn(),
    } as unknown as ReturnType<typeof useSearchQuery>);

    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: 'mario',
        initialScope: 'games',
        initialPage: 1,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/of 5/i)).toBeVisible();
    expect(screen.getByRole('combobox')).toHaveValue('1');
  });

  it('given the user clicks the next page button, updates the current page', async () => {
    // ARRANGE
    const mockSearchResults: SearchResults = {
      results: {
        games: [createGame()],
      },
      query: 'mario',
      scopes: ['games'],
      scopeRelevance: { games: 100 },
      pagination: {
        currentPage: 1,
        lastPage: 5,
        perPage: 50,
        total: 250,
      },
    };

    vi.mocked(useSearchQuery).mockReturnValue({
      data: mockSearchResults,
      isLoading: false,
      setSearchTerm: vi.fn(),
      setShouldUsePlaceholderData: vi.fn(),
    } as unknown as ReturnType<typeof useSearchQuery>);

    render<App.Http.Data.SearchPageProps>(<SearchMainRoot />, {
      pageProps: {
        initialQuery: 'mario',
        initialScope: 'games',
        initialPage: 1,
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const nextPageItem = screen.getByRole('listitem', { name: /next page/i });
    // eslint-disable-next-line testing-library/no-node-access -- this is fine in this case
    const nextButton = nextPageItem.querySelector('button')!;
    await userEvent.click(nextButton);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveValue('2');
  });
});
