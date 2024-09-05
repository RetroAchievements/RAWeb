import { createFactory } from '../createFactory';
import { createGameHashLabel } from './createGameHashLabel';

export const createGameHash = createFactory<App.Platform.Data.GameHash>((faker) => {
  const labelsCount = faker.number.int({ min: 0, max: 2 });

  const labels: App.Platform.Data.GameHashLabel[] = [];
  for (let i = 0; i < labelsCount; i += 1) {
    labels.push(createGameHashLabel());
  }

  return {
    labels,
    id: faker.number.int({ min: 1, max: 99999 }),
    md5: faker.string.alphanumeric(32),
    name: faker.word.words(3),
    patchUrl: faker.internet.url(),
  };
});
