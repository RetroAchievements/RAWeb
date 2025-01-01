import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { UnlockEventContent } from './UnlockEventContent';

describe('Component: UnlockEventContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    const { container } = render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={false}
        hardcoreLater={false}
        previousEventKind="start-session"
        sessionType="player-session"
        when={null}
        whenPrevious={null}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an unofficial achievement, shows the unofficial label and applies grayscale', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 5, // !!
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={false}
        hardcoreLater={false}
        previousEventKind="unlock"
        sessionType="player-session"
        when={null}
        whenPrevious={null}
      />,
    );

    // ASSERT
    expect(screen.getByText(/unofficial achievement/i)).toBeVisible();
    expect(screen.getByRole('img')).toHaveClass('grayscale');
  });

  it('given a softcore unlock, shows the softcore label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={false}
        hardcoreLater={false}
        previousEventKind="unlock"
        sessionType="player-session"
        when={null}
        whenPrevious={null}
      />,
    );

    // ASSERT
    expect(screen.getByText(/softcore/i)).toBeVisible();
  });

  it('given a hardcore unlock, does not show the softcore label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="unlock"
        sessionType="player-session"
        when={null}
        whenPrevious={null}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/softcore/i)).not.toBeInTheDocument();
  });

  it('given a softcore unlock where a hardcore unlock happened later, shows the relevant label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={false}
        hardcoreLater={true} // !!
        previousEventKind="unlock"
        sessionType="player-session"
        when={null}
        whenPrevious={null}
      />,
    );

    // ASSERT
    expect(screen.getByText(/unlocked later in hardcore/i)).toBeVisible();
  });

  it('given an immediate unlock after the previous unlock, does not show sublabel content', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="unlock"
        sessionType="player-session"
        when="2024-01-01T00:00:00Z" // !!
        whenPrevious="2024-01-01T00:00:00Z" // !!
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('arrow-icon')).not.toBeInTheDocument();
  });

  it('given a unlock that was some time after the previous unlock, shows the proper timing label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="unlock"
        sessionType="player-session"
        when="2024-01-01T00:01:00Z" // !!
        whenPrevious="2024-01-01T00:00:00Z" // !!
      />,
    );

    // ASSERT
    expect(screen.getByText(/1m 0s after previous/i)).toBeVisible();
  });

  it('given an unlock after the session start, shows the proper timing label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="start-session" // !!
        sessionType="player-session"
        when="2024-01-01T00:01:00Z" // !!
        whenPrevious="2024-01-01T00:00:00Z" // !!
      />,
    );

    // ASSERT
    expect(screen.getByText(/1m 0s after session start/i)).toBeVisible();
  });

  it('given an instant unlock at session start, shows no timing label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="start-session" // !!
        sessionType="player-session"
        when="2024-01-01T00:00:00Z" // !!
        whenPrevious="2024-01-01T00:00:00Z" // !!
      />,
    );

    // ASSERT
    const timingLabels = [/after session start/i, /after previous/i, /immediately after/i];

    for (const label of timingLabels) {
      expect(screen.queryByText(label)).not.toBeInTheDocument();
    }
  });

  it('given the event is the first in a reconstructed session, displays the correct info label', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      title: 'Test Achievement',
      description: 'Test Description',
      points: 10,
      badgeUnlockedUrl: '/Images/1234.png',
      flags: 3,
    });

    render(
      <UnlockEventContent
        achievement={achievement}
        hardcore={true}
        hardcoreLater={false}
        previousEventKind="start-session" // !!
        sessionType="reconstructed"
        when="2024-01-01T00:00:00Z" // !!
        whenPrevious="2024-01-01T00:00:00Z" // !!
      />,
    );

    // ASSERT
    expect(screen.getByText(/start of reconstructed timeline/i)).toBeVisible();
  });
});
