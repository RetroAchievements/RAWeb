import { BaseTable, BaseTableBody } from '@/common/components/+vendor/BaseTable';
import { render, screen } from '@/test';

import { GameOtherNamesRow } from './GameOtherNamesRow';

describe('Component: GameOtherNamesRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseTable>
        <BaseTableBody>
          <GameOtherNamesRow nonCanonicalTitles={[]} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a single title, displays the title', () => {
    // ARRANGE
    const titles = ['Zelda II: The Adventure of Link'];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameOtherNamesRow nonCanonicalTitles={titles} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/zelda ii: the adventure of link/i)).toBeVisible();
  });

  it('given multiple titles, displays all titles', () => {
    // ARRANGE
    const titles = ['Super Mario Bros.', 'Super Mario Brothers', 'SMB'];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameOtherNamesRow nonCanonicalTitles={titles} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/super mario bros\./i)).toBeVisible();
    expect(screen.getByText(/super mario brothers/i)).toBeVisible();
    expect(screen.getByText(/smb/i)).toBeVisible();
  });

  it('given exactly 4 titles, displays all titles without a tooltip', () => {
    // ARRANGE
    const titles = ['Title 1', 'Title 2', 'Title 3', 'Title 4'];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameOtherNamesRow nonCanonicalTitles={titles} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/title 1/i)).toBeVisible();
    expect(screen.getByText(/title 2/i)).toBeVisible();
    expect(screen.getByText(/title 3/i)).toBeVisible();
    expect(screen.getByText(/title 4/i)).toBeVisible();
    expect(screen.queryByText(/more/i)).not.toBeInTheDocument();
  });

  it('given 5 titles, displays the first 3 titles and shows "+2 more" tooltip trigger', () => {
    // ARRANGE
    const titles = ['Title 1', 'Title 2', 'Title 3', 'Title 4', 'Title 5'];

    render(
      <BaseTable>
        <BaseTableBody>
          <GameOtherNamesRow nonCanonicalTitles={titles} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/title 1/i)).toBeVisible();
    expect(screen.getByText(/title 2/i)).toBeVisible();
    expect(screen.getByText(/title 3/i)).toBeVisible();
    expect(screen.getByText(/\+2 more/i)).toBeVisible();

    expect(screen.queryByText(/title 4/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/title 5/i)).not.toBeInTheDocument();
  });
});
