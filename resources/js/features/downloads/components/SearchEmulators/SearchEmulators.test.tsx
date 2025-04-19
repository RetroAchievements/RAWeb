import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { searchQueryAtom } from '../../state/downloads.atoms';
import { SearchEmulators } from './SearchEmulators';

describe('Component: SearchEmulators', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SearchEmulators />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user enters text in the search input, updates the search query atom', async () => {
    // ARRANGE
    render(<SearchEmulators />);

    const searchInput = screen.getByPlaceholderText(/search by name/i);

    // ACT
    await userEvent.type(searchInput, 'retro');

    // ASSERT
    expect(searchInput).toHaveValue('retro');
  });

  it('given the initial search query has a value, displays it in the input', () => {
    // ARRANGE
    render(<SearchEmulators />, {
      jotaiAtoms: [
        [searchQueryAtom, 'existing search'],
        //
      ],
    });

    // ASSERT
    expect(screen.getByPlaceholderText(/search by name/i)).toHaveValue('existing search');
  });
});
