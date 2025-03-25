import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createForumTopic } from '@/test/factories';

import { TopicOptions } from './TopicOptions';

describe('Component: TopicOptions', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TopicOptions />, {
      pageProps: { can: {} },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user clicks the options button, opens the options panel', async () => {
    // ARRANGE
    render(<TopicOptions />, {
      pageProps: { can: {}, forumTopic: createForumTopic() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /options/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeVisible();
  });

  it('given the user has permission to manage forum topics, shows the manage form', async () => {
    // ARRANGE
    render(<TopicOptions />, {
      pageProps: { can: { manageForumTopics: true }, forumTopic: createForumTopic() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /options/i }));

    // ASSERT
    expect(screen.getByRole('form')).toBeVisible();
  });

  it('given the user has permission to delete forum topics, shows the delete button', async () => {
    // ARRANGE
    render(<TopicOptions />, {
      pageProps: { can: { deleteForumTopic: true }, forumTopic: createForumTopic() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /options/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /delete/i })).toBeVisible();
  });

  it('given the user lacks permissions, hides the management options', async () => {
    // ARRANGE
    render(<TopicOptions />, {
      pageProps: {
        can: { manageForumTopics: false, deleteForumTopic: false },
        forumTopic: createForumTopic(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /options/i }));

    // ASSERT
    expect(screen.queryByRole('form')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /delete/i })).not.toBeInTheDocument();
  });
});
