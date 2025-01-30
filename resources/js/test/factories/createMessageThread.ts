import { createFactory } from '../createFactory';
import { createMessage } from './createMessage';

export const createMessageThread = createFactory<App.Community.Data.MessageThread>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 999_999 }),
    isUnread: faker.datatype.boolean(),
    lastMessage: createMessage(),
    numMessages: faker.number.int({ min: 1, max: 20 }),
    title: faker.word.words(12),
    messages: [createMessage()],
  };
});
