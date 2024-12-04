import userEvent from '@testing-library/user-event';
import * as ReactUseModule from 'react-use';

import { __UNSAFE_VERY_DANGEROUS_SLEEP, render, screen, waitFor } from '@/test';

import { ActivePlayerSearchBar } from './ActivePlayerSearchBar';

describe('Component: ActivePlayerSearchBar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ActivePlayerSearchBar onSearch={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no persisted search, does not prepopulate a search value', () => {
    // ARRANGE
    render(<ActivePlayerSearchBar onSearch={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('textbox', { name: /search/i })).toHaveValue('');
    expect(screen.getByRole('checkbox', { name: /remember/i })).not.toBeChecked();
  });

  it('given there is a persisted search, prepopulates the search value and ticks the checkbox', () => {
    // ARRANGE
    render(<ActivePlayerSearchBar onSearch={vi.fn()} persistedSearchValue="developing|fixing" />);

    // ASSERT
    expect(screen.getByRole('textbox', { name: /search/i })).toHaveValue('developing|fixing');
    expect(screen.getByRole('checkbox', { name: /remember/i })).toBeChecked();
  });

  it('debounces the search when the user types', async () => {
    // ARRANGE
    const onSearch = vi.fn();

    render(<ActivePlayerSearchBar onSearch={onSearch} />);

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /search/i }), 'test'); // the first is ignored under test
    await __UNSAFE_VERY_DANGEROUS_SLEEP(600);
    await userEvent.type(screen.getByRole('textbox', { name: /search/i }), 'test');

    // ASSERT
    await waitFor(() => {
      expect(onSearch).toHaveBeenCalledOnce();
    });
  });

  it('given the remember option is checked, persists the search value to a cookie', async () => {
    // ARRANGE
    const setCookie = vi.fn();

    vi.spyOn(ReactUseModule, 'useCookie').mockReturnValue([null, setCookie, vi.fn()]);

    render(<ActivePlayerSearchBar onSearch={vi.fn()} />);

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /remember/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /search/i }), 'test');
    await __UNSAFE_VERY_DANGEROUS_SLEEP(600);
    await userEvent.type(screen.getByRole('textbox', { name: /search/i }), 'test');

    await waitFor(() => {
      expect(setCookie).toHaveBeenCalledWith('testtest');
    });
  });

  it('deletes the persist cookie when the remember option is unchecked', async () => {
    // ARRANGE
    const deleteCookie = vi.fn();

    vi.spyOn(ReactUseModule, 'useCookie').mockReturnValue([null, vi.fn(), deleteCookie]);

    render(<ActivePlayerSearchBar onSearch={vi.fn()} persistedSearchValue="test" />);

    // ACT
    await userEvent.click(screen.getByRole('checkbox', { name: /remember/i }));

    // ASSERT
    expect(deleteCookie).toHaveBeenCalled();
  });
});
