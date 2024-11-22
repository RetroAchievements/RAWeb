import type { ZiggyProps } from '@/common/models';

import { createFactory } from '../createFactory';

export const createZiggyProps = createFactory<ZiggyProps>((faker) => {
  return {
    defaults: [],
    device: 'desktop',
    location: faker.internet.url(),
    port: 8080,
    query: {},
    url: faker.internet.url(),
  };
});
