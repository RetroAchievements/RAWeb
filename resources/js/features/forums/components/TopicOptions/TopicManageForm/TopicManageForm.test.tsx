import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createForumTopic } from '@/test/factories';

import { TopicManageForm } from './TopicManageForm';

describe('Component: TopicManageForm', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TopicManageForm />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1 }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the topic has required permissions set, pre-selects that permission level', () => {
    // ARRANGE
    render(<TopicManageForm />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1, requiredPermissions: 4 }),
      },
    });

    // ASSERT
    expect(screen.getAllByText(/moderator/i)[1]).toBeVisible();
  });

  it('given the user changes the permission level and submits, makes a request to update the topic', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });

    render(<TopicManageForm />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 123, requiredPermissions: 0 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getAllByRole('option', { name: /developer/i })[0]);
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(putSpy).toHaveBeenCalledWith(route('api.forum-topic.gate', { topic: 123 }), {
        permissions: 2,
      });
    });
  });
});
