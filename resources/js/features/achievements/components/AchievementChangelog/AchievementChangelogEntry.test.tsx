import { render, screen, within } from '@/test';
import { createAchievementChangelogEntry, createUser } from '@/test/factories';

import { AchievementChangelogEntry } from './AchievementChangelogEntry';

describe('Component: AchievementChangelogEntry', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ul>
        <AchievementChangelogEntry entry={createAchievementChangelogEntry({ type: 'edited' })} />
      </ul>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it.each([
    ['created', 'Created'],
    ['deleted', 'Deleted'],
    ['restored', 'Restored'],
    ['edited', 'Edited'],
    ['promoted', 'Promoted'],
    ['demoted', 'Demoted'],
    ['description-updated', 'Description updated'],
    ['title-updated', 'Title updated'],
    ['badge-updated', 'Badge updated'],
    ['embed-url-updated', 'Embed URL updated'],
    ['logic-updated', 'Logic updated'],
    ['moved-to-different-game', 'Transferred to a different achievement set'],
    ['type-changed', 'Type changed'],
    ['type-removed', 'Type removed'],
  ] as const)('given type is %s, displays "%s"', (type, expectedText) => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry entry={createAchievementChangelogEntry({ type })} />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText(expectedText)).toBeVisible();
  });

  it('given the type is points-changed with field changes, displays old and new values as a diff', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'points-changed',
            fieldChanges: [{ oldValue: '10', newValue: '25' }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Points changed')).toBeVisible();
    expect(screen.getByText('10')).toBeVisible();
    expect(screen.getByText('25')).toBeVisible();
  });

  it('given the type is points-changed without field changes, displays a generic label', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({ type: 'points-changed' })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Points changed')).toBeVisible();
  });

  it('given the type is type-set with a field change, displays the type name inline', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'type-set',
            fieldChanges: [{ oldValue: null, newValue: 'missable' }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText(/type set to missable/i)).toBeVisible();
  });

  it('given the type is type-set without a field change, displays a generic label', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry entry={createAchievementChangelogEntry({ type: 'type-set' })} />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Type set')).toBeVisible();
  });

  it('given the type is badge-updated with field changes, renders before and after images', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'badge-updated',
            fieldChanges: [
              {
                oldValue: 'https://media.retroachievements.org/Badge/00000.png',
                newValue: 'https://media.retroachievements.org/Badge/12345.png',
              },
            ],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByAltText('Old badge')).toHaveAttribute(
      'src',
      'https://media.retroachievements.org/Badge/00000.png',
    );
    expect(screen.getByAltText('New badge')).toHaveAttribute(
      'src',
      'https://media.retroachievements.org/Badge/12345.png',
    );
  });

  it('given the type is type-removed with a field change, displays the old type name inline', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'type-removed',
            fieldChanges: [{ oldValue: 'Win Condition', newValue: null }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText(/removed type Win Condition/i)).toBeVisible();
  });

  it('given the type is moved-to-different-game with field changes, displays game names inline', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'moved-to-different-game',
            fieldChanges: [
              { oldValue: 'Super Mario Bros.', newValue: 'Super Mario Bros. [Subset - Bonus]' },
            ],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(
      screen.getByText(
        /transferred from Super Mario Bros\. to Super Mario Bros\. \[Subset - Bonus\]/i,
      ),
    ).toBeVisible();
  });

  it('given an unknown type, falls back to "Edited"', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({ type: 'something-unknown' as any })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Edited')).toBeVisible();
  });

  it.each([
    ['created', false, 'bg-blue-500'],
    ['created', true, 'bg-green-500'],
    ['deleted', undefined, 'bg-red-500'],
    ['demoted', undefined, 'bg-red-500'],
    ['restored', undefined, 'bg-blue-500'],
    ['promoted', undefined, 'bg-green-500'],
  ] as const)(
    'given the type is %s with isCreatedAsPromoted=%s, renders a %s dot',
    (type, isCreatedAsPromoted, expectedClass) => {
      // ARRANGE
      render(
        <ul>
          <AchievementChangelogEntry
            entry={createAchievementChangelogEntry({ type })}
            isCreatedAsPromoted={isCreatedAsPromoted}
          />
        </ul>,
      );

      // ASSERT
      const dot = screen.getByTestId('changelog-dot');
      expect(dot).toHaveClass(expectedClass);
    },
  );

  it('given an edited entry with a count greater than 1, displays the count', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({ type: 'edited', count: 5 })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText(/\(5 times\)/i)).toBeVisible();
  });

  it('given an edited entry with a count of 1, does not display a count', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry entry={createAchievementChangelogEntry({ type: 'edited' })} />
      </ul>,
    );

    // ASSERT
    expect(screen.queryByText(/times/i)).not.toBeInTheDocument();
  });

  it('given a non-edited entry with a count greater than 1, does not display a count', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({ type: 'logic-updated', count: 3 })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.queryByText(/times/i)).not.toBeInTheDocument();
  });

  it('given an edited entry before the detailed field tracking cutoff, shows a tooltip trigger', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2020-06-10T08:00:00Z',
          })}
        />
      </ul>,
    );

    // ASSERT
    const entry = screen.getByTestId('changelog-entry');
    expect(within(entry).getByRole('button')).toBeVisible();
  });

  it.each([
    ['edited', '2025-06-10T08:00:00Z'],
    ['logic-updated', '2020-06-10T08:00:00Z'],
  ] as const)(
    'given the type is %s with createdAt %s, does not show a tooltip trigger',
    (type, createdAt) => {
      // ARRANGE
      render(
        <ul>
          <AchievementChangelogEntry entry={createAchievementChangelogEntry({ type, createdAt })} />
        </ul>,
      );

      // ASSERT
      const entry = screen.getByTestId('changelog-entry');
      expect(within(entry).queryByRole('button')).not.toBeInTheDocument();
    },
  );

  it('given changes with both old and new values, displays both', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'description-updated',
            fieldChanges: [{ oldValue: 'Old text', newValue: 'New text' }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Old text')).toBeVisible();
    expect(screen.getByText('New text')).toBeVisible();
  });

  it('given a field change with only a new value, displays only the new value', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'description-updated',
            fieldChanges: [{ oldValue: null, newValue: 'Brand new description' }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Brand new description')).toBeVisible();
  });

  it('given a field change with only an old value, displays only the old value', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'description-updated',
            fieldChanges: [{ oldValue: 'Removed description', newValue: null }],
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('Removed description')).toBeVisible();
  });

  it('given the entry has a user, displays the user', () => {
    // ARRANGE
    render(
      <ul>
        <AchievementChangelogEntry
          entry={createAchievementChangelogEntry({
            type: 'edited',
            user: createUser({ displayName: 'DevAuthor' }),
          })}
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByText('DevAuthor')).toBeVisible();
  });
});
