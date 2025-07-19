import axios from 'axios';
import { route } from 'ziggy-js';

import { act, renderHook, waitFor } from '@/test';
import { createUser } from '@/test/factories';

import { useUserSearchQuery } from './useUserSearchQuery';

vi.mock('axios');
vi.mock('ziggy-js', () => ({
  route: vi.fn(),
}));

describe('Hook: useUserSearchQuery', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(route).mockReturnValue('/api/search' as any);
  });

  it('returns initial state with empty search term', () => {
    // ARRANGE
    const { result } = renderHook(() => useUserSearchQuery());

    // ASSERT
    expect(result.current.searchTerm).toBe('');
    expect(result.current.setSearchTerm).toBeInstanceOf(Function);
    expect(result.current.data).toBeUndefined();
    expect(result.current.isLoading).toBe(false);
  });

  it('given an initial search term, uses it as the default', () => {
    // ARRANGE
    const { result } = renderHook(() => useUserSearchQuery({ initialSearchTerm: 'john' }));

    // ASSERT
    expect(result.current.searchTerm).toBe('john');
  });

  it('given the search term is less than 3 characters, does not make an API call', async () => {
    // ARRANGE
    const { result } = renderHook(() => useUserSearchQuery());

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

  it('given the search term is 3 or more characters, fetches users', async () => {
    // ARRANGE
    const mockUsers = [createUser(), createUser()];
    const mockResponse = {
      data: {
        results: { users: mockUsers },
        query: 'test',
        scopes: ['users'],
        scopeRelevance: { users: 100 },
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useUserSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.data).toEqual(mockUsers);
    });
    expect(axios.get).toHaveBeenCalledWith('/api/search?q=test&scope=users');
  });

  it('given no users in response, returns empty array', async () => {
    // ARRANGE
    const mockResponse = {
      data: {
        results: {},
        query: 'test',
        scopes: ['users'],
        scopeRelevance: {},
      },
    };
    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse as any);
    const { result } = renderHook(() => useUserSearchQuery());

    // ACT
    act(() => {
      result.current.setSearchTerm('test');
    });

    // ASSERT
    await waitFor(() => {
      expect(result.current.data).toEqual([]);
    });
  });
});
