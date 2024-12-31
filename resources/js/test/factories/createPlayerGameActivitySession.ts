import { createFactory } from '../createFactory';
import { createGameHash } from './createGameHash';
import { createParsedUserAgent } from './createParsedUserAgent';
import { createPlayerGameActivityEvent } from './createPlayerGameActivityEvent';

export const createPlayerGameActivitySession =
  createFactory<App.Platform.Data.PlayerGameActivitySession>((faker) => {
    return {
      type: 'player-session',
      startTime: faker.date.recent().toISOString(),
      endTime: faker.date.recent().toISOString(),
      duration: faker.number.int({ min: 1, max: 10_000 }),
      userAgent: 'RetroArch/1.19.1 (Linux 4.9) mgba_libretro/0.11-dev',
      parsedUserAgent: createParsedUserAgent(),
      gameHash: createGameHash(),
      events: [
        createPlayerGameActivityEvent(),
        createPlayerGameActivityEvent(),
        createPlayerGameActivityEvent(),
        createPlayerGameActivityEvent(),
      ],
    };
  });
