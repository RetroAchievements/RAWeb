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

  it('given elements with hrefs, renders them as links', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[
              { label: 'First', href: '/first' },
              { label: 'Second', href: '/second' },
            ]}
          />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    const firstLink = screen.getByRole('link', { name: /first/i });
    expect(firstLink).toBeVisible();
    expect(firstLink).toHaveAttribute('href', '/first');

    const secondLink = screen.getByRole('link', { name: /second/i });
    expect(secondLink).toBeVisible();
    expect(secondLink).toHaveAttribute('href', '/second');
  });

  it('given a mix of links and text elements, renders them correctly', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <GameMetadataRow
            rowHeading="Test"
            elements={[
              { label: 'First', href: '/first' },
              { label: 'Second' },
              { label: 'Third', href: '/third' },
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
});
