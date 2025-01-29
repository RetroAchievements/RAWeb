import { render, screen } from '@/test';
import { createMessageThread } from '@/test/factories';

import { MessagesTable } from './MessagesTable';

describe('Component: MessagesTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesTable />, {
      pageProps: {
        paginatedMessageThreads: {
          items: [],
        },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are message threads, renders them in the table', () => {
    // ARRANGE
    const threads = [createMessageThread(), createMessageThread()];

    render(<MessagesTable />, {
      pageProps: {
        paginatedMessageThreads: {
          items: threads,
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();
    expect(screen.getAllByRole('row')).toHaveLength(3); // header row + 2 data rows
  });

  it('displays the correct column headers', () => {
    // ARRANGE
    render(<MessagesTable />, {
      pageProps: {
        paginatedMessageThreads: {
          items: [],
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /subject/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /with/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /message count/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /last message/i })).toBeVisible();
  });
});
