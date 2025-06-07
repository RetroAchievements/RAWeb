import axios from 'axios';
import { route } from 'ziggy-js';

import { act, renderHook, waitFor } from '@/test';
import { createGame, createUser } from '@/test/factories';

import { useSearchQuery } from './useSearchQuery';

vi.mock('axios');
vi.mock('ziggy-js', () => ({
  route: vi.fn(),
}));

describe('Hook: useSearchQuery', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(route).mockReturnValue('/api/search' as any);
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  it('returns initial state with empty search term', () => {
    // ARRANGE
    const { result } = renderHook(() => useSearchQuery());

    // ASSERT
    expect(result.current.searchTerm).toEqual('');
    expect(result.current.setSearchTerm).toBeInstanceOf(Function);
    expect(result.current.data).toBeUndefined();
    expect(result.current.isLoading).toBe(false);
  });

  it('given an initial search term, uses it as the default', () => {
    // ARRANGE
    const { result } = renderHook(() => useSearchQuery({ initialSearchTerm: 'mario' }));

    // ASSERT
    expect(result.current.searchTerm).toEqual('mario');
  });

  it('given setSearchTerm is called, updates the search term', () => {
    // ARRANGE
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('sonic');
    });

    // ASSERT
    expect(result.current.searchTerm).toEqual('sonic');
  });

  it('given the search term is less than 3 characters, does not make an API call', async () => {
    // ARRANGE
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('ab');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).not.toHaveBeenCalled();
    });
    expect(result.current.isLoading).toBe(false);
  });

  it('given the search term is 3 or more characters, makes an API call', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: { users: [createUser()] },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 1 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith('/api/search?q=test&scope=users');
    });
  });

  it('given a custom route is provided, uses it instead of the default', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: { users: [] },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 0 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery({ route: '/internal-api/search' }));

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith('/internal-api/search?q=test&scope=users');
    });
    expect(route).not.toHaveBeenCalled();
  });

  it('given no custom route is provided, uses the default ziggy route', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: { users: [] },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 0 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(route).toHaveBeenCalledWith('api.search.index');
    });

    expect(axios.get).toHaveBeenCalledWith('/api/search?q=test&scope=users');
  });

  it('given custom scopes are provided, includes them in the query', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: { games: [createGame()], users: [createUser()] },
        query: 'test',
        scopes: ['games', 'users'],
        scopeRelevance: { games: 0.8, users: 0.5 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery({ scopes: ['games', 'users'] }));

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith('/api/search?q=test&scope=games%2Cusers');
    });
  });

  it('given no scopes are provided, defaults to the users scope', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: { users: [] },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 1 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith('/api/search?q=test&scope=users');
    });
  });

  it('given empty scopes array, does not append scope parameter', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: {},
        query: 'test',
        scopes: [],
        scopeRelevance: {},
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery({ scopes: [] }));

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith('/api/search?q=test');
    });
  });

  it('returns the search results data when API call succeeds', async () => {
    // ARRANGE
    const mockUsers = [createUser({ displayName: 'TestUser' })];
    const mockResponse = {
      data: {
        results: { users: mockUsers },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 1 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.data).toEqual(mockResponse.data);
    });

    expect(result.current.data?.results.users).toEqual(mockUsers);
  });

  it('shows a loading state while fetching', async () => {
    // ARRANGE
    vi.mocked(axios.get).mockImplementation(
      () => new Promise((resolve) => setTimeout(resolve, 100)) as any,
    );
    const { result } = renderHook(() => useSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.isLoading).toBe(true);
    });
  });
});
