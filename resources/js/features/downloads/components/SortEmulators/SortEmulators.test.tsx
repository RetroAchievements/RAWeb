import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { sortByAtom } from '../../state/downloads.atoms';
import { SortEmulators } from './SortEmulators';

describe('Component: SortEmulators', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SortEmulators />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a default sort order, shows it as the selected value', () => {
    // ARRANGE
    render(<SortEmulators />, {
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent(/popularity/i);
  });

  it('given the user changes the sort order, updates the atom value', async () => {
    // ARRANGE
    render(<SortEmulators />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText(/name/i));

    // ASSERT
    const sortByValue = screen.getByRole('combobox').textContent;
    expect(sortByValue).toEqual('Name (A-Z)');
  });
});
