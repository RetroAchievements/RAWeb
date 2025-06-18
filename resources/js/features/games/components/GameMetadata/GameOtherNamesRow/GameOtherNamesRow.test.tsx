import { render, screen } from '@/test';

import { GameOtherNamesRow } from './GameOtherNamesRow';

describe('Component: GameOtherNamesRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameOtherNamesRow nonCanonicalTitles={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a single title, displays the title', () => {
    // ARRANGE
    const titles = ['Zelda II: The Adventure of Link'];

    // ACT
    render(<GameOtherNamesRow nonCanonicalTitles={titles} />);

    // ASSERT
    expect(screen.getByText(/zelda ii: the adventure of link/i)).toBeVisible();
  });

  it('given multiple titles, displays all titles', () => {
    // ARRANGE
    const titles = ['Super Mario Bros.', 'Super Mario Brothers', 'SMB'];

    // ACT
    render(<GameOtherNamesRow nonCanonicalTitles={titles} />);

    // ASSERT
    expect(screen.getByText(/super mario bros\./i)).toBeVisible();
    expect(screen.getByText(/super mario brothers/i)).toBeVisible();
    expect(screen.getByText(/smb/i)).toBeVisible();
  });
});
