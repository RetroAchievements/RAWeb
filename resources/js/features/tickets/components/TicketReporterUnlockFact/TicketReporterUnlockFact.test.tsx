import { render } from '@/test';
import { createTicketListEntry, createUser } from '@/test/factories';

import { TicketReporterUnlockFact } from './TicketReporterUnlockFact';

describe('Component: TicketReporterUnlockFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-05-03T12:00:00Z',
          isHardcore: false,
          unlocker: null,
        }}
        ticket={createTicketListEntry({ createdAt: '2024-05-01T12:00:00Z' })}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a casual unlock that followed the report, states it', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-05-03T12:00:00Z',
          isHardcore: false,
          unlocker: null,
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott unlocked this achievement in casual mode 2 days after the report.',
    );
  });

  it('given a casual unlock that preceded the report, states it', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-04-29T12:00:00Z',
          isHardcore: false,
          unlocker: null,
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott unlocked this achievement in casual mode 2 days before the report.',
    );
  });

  it('given a hardcore unlock that followed the report, states it', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-05-03T12:00:00Z',
          isHardcore: true,
          unlocker: null,
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott unlocked this achievement in hardcore mode 2 days after the report.',
    );
  });

  it('given a hardcore unlock that preceded the report, states it', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-04-29T12:00:00Z',
          isHardcore: true,
          unlocker: null,
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott unlocked this achievement in hardcore mode 2 days before the report.',
    );
  });

  it('given a manual unlock that followed the report, states the awarder and the reporter', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-05-03T12:00:00Z',
          isHardcore: false,
          unlocker: createUser({ displayName: 'Scott' }),
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'PixelHero' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott manually unlocked this achievement for PixelHero 2 days after the report.',
    );
  });

  it('given a manual unlock that preceded the report, states the awarder and the reporter', () => {
    // ARRANGE
    const { container } = render(
      <TicketReporterUnlockFact
        reporterUnlock={{
          unlockedAt: '2024-04-29T12:00:00Z',
          isHardcore: false,
          unlocker: createUser({ displayName: 'Scott' }),
        }}
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T12:00:00Z',
          reporter: createUser({ displayName: 'PixelHero' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott manually unlocked this achievement for PixelHero 2 days before the report.',
    );
  });
});
