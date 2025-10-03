import { BaseTable, BaseTableBody } from '@/common/components/+vendor/BaseTable';
import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { GameMetadataRow } from './GameMetadataRow';

describe('Component: GameMetadataRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow rowHeading="Test" elements={[{ label: 'Test Element' }]} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no elements are provided, returns null', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow rowHeading="Test" elements={[]} />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.queryByRole('row')).not.toBeInTheDocument();
  });

  it('given an array of text elements, formats them according to the locale', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[{ label: 'First' }, { label: 'Second' }, { label: 'Third' }]}
          />
        </BaseTableBody>
      </BaseTable>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ locale: 'en_US' }) },
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/first/i)).toBeVisible();
    expect(screen.getByText(/second/i)).toBeVisible();
    expect(screen.getByText(/third/i)).toBeVisible();
  });

  it('given elements with hub IDs, renders them as links', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[
              { label: 'First', hubId: 1 },
              { label: 'Second', hubId: 2 },
            ]}
          />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    const firstLink = screen.getByRole('link', { name: /first/i });
    expect(firstLink).toBeVisible();

    const secondLink = screen.getByRole('link', { name: /second/i });
    expect(secondLink).toBeVisible();
  });

  it('given a mix of links and text elements, renders them correctly', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[
              { label: 'First', hubId: 1 },
              { label: 'Second' },
              { label: 'Third', hubId: 3 },
            ]}
          />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /first/i })).toBeVisible();
    expect(screen.getByText(/second/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /third/i })).toBeVisible();
  });

  it('given no user locale is set, does not crash', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow rowHeading="Test" elements={[{ label: 'First' }, { label: 'Second' }]} />
        </BaseTableBody>
      </BaseTable>,
      {
        pageProps: {
          auth: null,
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/first/i)).toBeVisible();
    expect(screen.getByText(/second/i)).toBeVisible();
  });

  it('given areListSeparatorsEnabled is false, does not render list separators', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[{ label: 'First' }, { label: 'Second' }, { label: 'Third' }]}
            areListSeparatorsEnabled={false} // !!
          />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.queryByText(',')).not.toBeInTheDocument();
    expect(screen.getByText(/first/i)).toBeVisible();
    expect(screen.getByText(/second/i)).toBeVisible();
    expect(screen.getByText(/third/i)).toBeVisible();
  });

  it('given areListSeparatorsEnabled is true, renders elements with locale-formatted separators', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[{ label: 'First' }, { label: 'Second' }, { label: 'Third' }]}
            areListSeparatorsEnabled={true} // !!
          />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/first/i)).toBeVisible();
    expect(screen.getByText(/second/i)).toBeVisible();
    expect(screen.getByText(/third/i)).toBeVisible();

    expect(screen.getAllByText(',')).toHaveLength(2);
  });
});
