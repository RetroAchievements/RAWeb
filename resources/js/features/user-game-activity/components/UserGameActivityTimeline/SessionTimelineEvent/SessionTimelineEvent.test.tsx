import { render, screen } from '@/test';
import { createAchievement, createPlayerGameActivityEvent } from '@/test/factories';

import { SessionTimelineEvent } from './SessionTimelineEvent';

describe('Component: SessionTimelineEvent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SessionTimelineEvent
        previousEventKind="start-session"
        previousEventTimestamp={null}
        sessionEvent={createPlayerGameActivityEvent({
          type: 'rich-presence',
          when: '2024-01-01T12:34:56Z',
          description: 'In Menu',
        })}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a rich presence event, renders the time and description', () => {
    // ARRANGE
    render(
      <SessionTimelineEvent
        previousEventKind="start-session"
        previousEventTimestamp={null}
        sessionEvent={createPlayerGameActivityEvent({
          type: 'rich-presence',
          when: '2024-01-01T12:34:56Z',
          description: 'In Menu',
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/12:34:56/i)).toBeVisible();
    expect(screen.getByText(/in menu/i)).toBeVisible();
  });

  it('given an unlock event, renders the time and achievement content', () => {
    // ARRANGE
    render(
      <SessionTimelineEvent
        previousEventKind="start-session"
        previousEventTimestamp={null}
        sessionEvent={createPlayerGameActivityEvent({
          type: 'unlock',
          when: '2024-01-01T12:34:56Z',
          achievement: createAchievement({ title: 'Test Achievement' }),
          hardcore: true,
          hardcoreLater: false,
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/12:34:56/i)).toBeVisible();
    expect(screen.getByText(/test achievement/i)).toBeVisible();
  });

  it('given there is no achievement associated with an unlock event, does not crash', () => {
    // ARRANGE
    render(
      <SessionTimelineEvent
        previousEventKind="start-session"
        previousEventTimestamp={null}
        sessionEvent={createPlayerGameActivityEvent({
          type: 'unlock',
          when: '2024-01-01T12:34:56Z',
          achievement: null,
          hardcore: true,
          hardcoreLater: false,
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/12:34:56/i)).toBeVisible();
  });

  it('given no description for a rich presence event, does not crash', () => {
    // ARRANGE
    render(
      <SessionTimelineEvent
        previousEventKind="start-session"
        previousEventTimestamp={null}
        sessionEvent={createPlayerGameActivityEvent({
          type: 'rich-presence',
          when: '2024-01-01T12:34:56Z',
          description: null,
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/12:34:56/i)).toBeVisible();
  });
});
