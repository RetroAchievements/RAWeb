import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { SearchMainRoot } from './SearchMainRoot';

describe('Component: SearchMainRoot', () => {
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
    expect(screen.getByRole('textbox')).toBeVisible();
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
    expect(screen.getByRole('textbox')).toHaveValue('mario');
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
    const input = screen.getByRole('textbox');
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
});
