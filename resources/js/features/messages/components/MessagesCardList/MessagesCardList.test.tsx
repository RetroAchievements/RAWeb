import { render, screen } from '@/test';
import { createMessageThread } from '@/test/factories';

import { MessagesCardList } from './MessagesCardList';

describe('Component: MessagesCardList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesCardList />, {
      pageProps: {
        paginatedMessageThreads: {
          items: [],
        },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are message threads, renders a card for each thread', () => {
    // ARRANGE
    const threads = [
      createMessageThread({ title: 'First Thread' }),
      createMessageThread({ title: 'Second Thread' }),
    ];

    render(<MessagesCardList />, {
      pageProps: {
        paginatedMessageThreads: {
          items: threads,
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/first thread/i)).toBeVisible();
    expect(screen.getByText(/second thread/i)).toBeVisible();
  });
});
