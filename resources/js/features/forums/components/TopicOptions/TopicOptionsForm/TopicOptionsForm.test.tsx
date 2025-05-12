import { faker } from '@faker-js/faker';
import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createForumTopic } from '@/test/factories';

import { TopicOptionsForm } from './TopicOptionsForm';

describe('Component: TopicOptionsForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TopicOptionsForm />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1, title: faker.lorem.words() }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the form loads, fills the title field with the existing topic title', () => {
    // ARRANGE
    const existingTitle = faker.lorem.words();

    render(<TopicOptionsForm />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1, title: existingTitle }),
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/topic title/i)).toHaveValue(existingTitle);
  });

  it('given the user submits the form with valid data, makes a request to update the topic', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: {} });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());

    const topicId = faker.number.int();
    const newTitle = 'this is my new title';

    render(<TopicOptionsForm />, {
      pageProps: {
        forumTopic: {
          id: topicId,
          title: faker.lorem.words(),
        },
      },
    });

    // ACT
    await userEvent.clear(screen.getByLabelText(/topic title/i));
    await userEvent.type(screen.getByLabelText(/topic title/i), newTitle);
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(putSpy).toHaveBeenCalledWith(route('api.forum-topic.update', { topic: topicId }), {
        title: newTitle,
      });
    });
  });

  it('given the user types a title that is too short, disables the submit button', async () => {
    // ARRANGE
    render(<TopicOptionsForm />, {
      pageProps: {
        forumTopic: {
          id: 1,
          title: faker.lorem.words(),
        },
      },
    });

    // ACT
    await userEvent.clear(screen.getByLabelText(/topic title/i));
    await userEvent.type(screen.getByLabelText(/topic title/i), 'a');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });
});
