import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createAchievementChangelogEntry, createUser } from '@/test/factories';

import { AchievementChangelog } from './AchievementChangelog';

describe('Component: AchievementChangelog', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no changelog entries, shows an empty state message', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/no changelog entries found/i)).toBeVisible();
  });

  it('given there are changelog entries, displays entry headers', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({ type: 'description-updated' }),
          createAchievementChangelogEntry({ type: 'badge-updated' }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/description updated/i)).toBeVisible();
    expect(screen.getByText(/badge updated/i)).toBeVisible();
  });

  it('given an entry has field changes, displays old and new values', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'description-updated',
            fieldChanges: [{ oldValue: 'Old description text', newValue: 'New description text' }],
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/old description text/i)).toBeVisible();
    expect(screen.getByText(/new description text/i)).toBeVisible();
  });

  it('given an entry has a user, displays the user', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'logic-updated',
            user: createUser({ displayName: 'TestDev' }),
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/testdev/i)).toBeVisible();
  });

  it('given an entry has no user, does not crash', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2020-06-10T08:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/edited/i)).toBeVisible();
  });

  it('given multiple entries, renders the correct number of entries', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({ type: 'logic-updated' }),
          createAchievementChangelogEntry({ type: 'title-updated' }),
          createAchievementChangelogEntry({ type: 'badge-updated' }),
        ],
      },
    });

    // ASSERT
    expect(screen.getAllByTestId('changelog-entry')).toHaveLength(3);
  });

  it('given entries with a promotion and pre-promotion work, shows an "Initial development" collapsible', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'logic-updated',
            createdAt: '2025-03-20T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-03-05T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/initial development/i)).toBeVisible();
  });

  it('given entries with a promotion but no pre-promotion work, does not show a collapsible', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'logic-updated',
            createdAt: '2025-03-20T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.queryByText(/initial development/i)).not.toBeInTheDocument();
  });

  it('given an achievement was created as promoted (has demotion but no promotion), does not collapse', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'demoted',
            createdAt: '2025-03-20T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-03-05T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.queryByText(/initial development/i)).not.toBeInTheDocument();
    expect(screen.getAllByTestId('changelog-entry')).toHaveLength(3);
  });

  it('given a created-as-promoted achievement was demoted and re-promoted, does not collapse pre-promotion entries', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'logic-updated',
            createdAt: '2025-04-01T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-20T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'demoted',
            createdAt: '2025-03-10T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-03-05T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.queryByText(/initial development/i)).not.toBeInTheDocument();
    expect(screen.getAllByTestId('changelog-entry')).toHaveLength(5);
  });

  it('given the collapsible is clicked, reveals the pre-promotion entries', async () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'logic-updated',
            createdAt: '2025-03-05T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-02-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ACT
    await userEvent.click(screen.getByText(/initial development/i));

    // ASSERT
    expect(screen.getAllByTestId('changelog-entry').length).toBeGreaterThanOrEqual(4);
  });

  it('given entries with a collapsible section, still shows the created entry outside the collapsible', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-03-05T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'created',
            createdAt: '2025-01-01T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/initial development/i)).toBeVisible();
    expect(screen.getByText(/created/i)).toBeVisible();
  });

  it('given entries with a promotion but no created entry, still renders the collapsible without crashing', () => {
    // ARRANGE
    render(<AchievementChangelog />, {
      pageProps: {
        achievement: createAchievement(),
        changelog: [
          createAchievementChangelogEntry({
            type: 'promoted',
            createdAt: '2025-03-15T10:00:00Z',
          }),
          createAchievementChangelogEntry({
            type: 'edited',
            createdAt: '2025-03-05T10:00:00Z',
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/initial development/i)).toBeVisible();
    expect(screen.queryByText(/created/i)).not.toBeInTheDocument();
  });
});
