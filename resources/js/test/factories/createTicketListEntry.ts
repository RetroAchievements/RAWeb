import { createFactory } from '../createFactory';
import { createEmulator } from './createEmulator';
import { createGame } from './createGame';
import { createGameHash } from './createGameHash';
import { createUser } from './createUser';

export const createTicketListEntry = createFactory<App.Platform.Data.TicketListEntry>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    state: faker.helpers.arrayElement(['open', 'request', 'quarantined', 'resolved', 'closed']),
    type: faker.helpers.arrayElement(['did_not_trigger', 'triggered_at_wrong_time']),
    hardcore: faker.datatype.boolean(),
    createdAt: faker.date.recent().toISOString(),
    resolvedAt: null,
    ticketableType: 'achievement',
    ticketableId: faker.number.int({ min: 1, max: 99999 }),
    ticketableTitle: faker.word.words(3),
    ticketableBadgeUrl: faker.internet.url(),
    game: createGame(),
    author: createUser(),
    reporter: createUser(),
    resolver: null,
    emulator: createEmulator(),
    emulatorVersion: faker.system.semver(),
    emulatorCore: null,
    gameHash: createGameHash(),
  };
});
