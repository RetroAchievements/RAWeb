import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createTicketListEntry, createUser } from '@/test/factories';

import { TicketReporterNoUnlockFact } from './TicketReporterNoUnlockFact';

describe('Component: TicketReporterNoUnlockFact', () => {
  it('given the reporter has no unlock, states that they have not unlocked the achievement', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser({ displayName: 'Rowan' })}
        ticket={createTicketListEntry()}
      />,
      { pageProps: { can: {} } },
    );

    // ASSERT
    expect(screen.getByText(/has not unlocked this achievement/i)).toBeVisible();
    expect(screen.getByText('Rowan')).toBeVisible();
  });

  it('given the viewer may not manually award achievements, does not render any award achievement action buttons', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser()}
        ticket={createTicketListEntry({ hardcore: true })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: false } } },
    );

    // ASSERT
    expect(screen.getByText(/has not unlocked this achievement/i)).toBeVisible();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the reporter is banned or deleted, does not render any award achievement action buttons', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser({ isGone: true })}
        ticket={createTicketListEntry({ hardcore: true })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: true } } },
    );

    // ASSERT
    expect(screen.getByText(/has not unlocked this achievement/i)).toBeVisible();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given a hardcore session ticket, positions the hardcore award action first and the casual award second', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser()}
        ticket={createTicketListEntry({ hardcore: true })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: true } } },
    );

    // ASSERT
    expect(screen.getAllByRole('button').map((element) => element.textContent)).toEqual([
      'Award hardcore',
      'Award casual instead',
    ]);
  });

  it('given a casual session ticket, offers only the casual award action', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser()}
        ticket={createTicketListEntry({ hardcore: false })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: true } } },
    );

    // ASSERT
    expect(screen.getAllByRole('button').map((element) => element.textContent)).toEqual([
      'Award casual',
    ]);
  });

  it('given the user picks the hardcore award action, pops a confirm before making an API call', async () => {
    // ARRANGE
    const awardPostSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ status: 204 });

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser()}
        ticket={createTicketListEntry({ hardcore: true })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: true } } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Award hardcore' }));

    // ASSERT
    expect(screen.getByText('Award a hardcore unlock?')).toBeVisible();
    expect(awardPostSpy.mock.calls).toEqual([]);
  });

  it('given the user confirms the casual alternative on a hardcore session ticket, sends a casual award unlock instead', async () => {
    // ARRANGE
    const awardPostSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ status: 204 });

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketReporterNoUnlockFact
        reporter={createUser()}
        ticket={createTicketListEntry({ id: 2468, hardcore: true })}
      />,
      { pageProps: { can: { manuallyAwardAchievements: true } } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Award casual instead' }));
    await userEvent.click(screen.getByRole('button', { name: 'Award casual' }));

    // ASSERT
    expect(awardPostSpy.mock.calls).toEqual([
      [route('api.ticket.reporter-unlock.store', { ticket: 2468 }), { isHardcore: false }],
    ]);
  });
});
