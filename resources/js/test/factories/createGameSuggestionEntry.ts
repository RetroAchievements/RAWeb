import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createGameSet } from './createGameSet';
import { createPlayerGame } from './createPlayerGame';
import { createUser } from './createUser';

export const createGameSuggestionEntry = createFactory<App.Platform.Data.GameSuggestionEntry>(
  (faker) => {
    return {
      game: createGame(),
      playerGame: createPlayerGame(),
      isInBacklog: faker.datatype.boolean(),
      suggestionContext: {
        relatedAuthor: createUser(),
        relatedGame: createGame(),
        relatedGameSet: createGameSet(),
        sourceGameKind: faker.helpers.arrayElement(['beaten', 'mastered']),
      },
      suggestionReason: faker.helpers.arrayElement([
        'common-players',
        'random',
        'revised',
        'shared-author',
        'shared-hub',
        'similar-game',
        'want-to-play',
      ]),
    };
  },
);
