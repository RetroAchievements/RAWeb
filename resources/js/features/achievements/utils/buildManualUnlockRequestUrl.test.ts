import { route } from 'ziggy-js';

import { createAchievement, createGame } from '@/test/factories';

import { buildManualUnlockRequestUrl } from './buildManualUnlockRequestUrl';

describe('Util: buildManualUnlockRequestUrl', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildManualUnlockRequestUrl).toBeDefined();
  });

  it('given no ticket id, builds a message to UnlockTeam that does not mention a ticket', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 90,
      title: 'Chaos Emerald Master',
      game: createGame({ title: 'Sonic 2' }),
    });

    // ACT
    buildManualUnlockRequestUrl(achievement);

    // ASSERT
    expect(route).toHaveBeenCalledWith('message-thread.create', {
      to: 'UnlockTeam',
      subject: 'Manual Unlock: Chaos Emerald Master [90] (Sonic 2)',
      message: `I'd like a manual unlock for [ach=90]:
(Provide link to video/screenshot showing evidence)`,
      templateKind: 'manual-unlock',
    });
  });

  it('given a ticket id, adds the ticket to the end of the message', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 100,
      title: 'Green Hill Zone Complete',
      game: createGame({ title: 'Sonic the Hedgehog' }),
    });

    // ACT
    buildManualUnlockRequestUrl(achievement, 789);

    // ASSERT
    expect(route).toHaveBeenCalledWith('message-thread.create', {
      to: 'UnlockTeam',
      subject: 'Manual Unlock: Green Hill Zone Complete [100] (Sonic the Hedgehog)',
      message: `I'd like a manual unlock for [ach=100]:
(Provide link to video/screenshot showing evidence)
Ticket: [ticket=789]`,
      templateKind: 'manual-unlock',
    });
  });
});
