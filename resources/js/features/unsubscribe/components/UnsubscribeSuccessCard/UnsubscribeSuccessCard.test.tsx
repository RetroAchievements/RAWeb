import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { UnsubscribeSuccessCard } from './UnsubscribeSuccessCard';

describe('Component: UnsubscribeSuccessCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />,
      {
        pageProps: {
          descriptionKey: null,
          descriptionParams: null,
          undoToken: null,
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a descriptionKey, displays the unsubscribe message', () => {
    // ARRANGE
    render(<UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />, {
      pageProps: {
        descriptionKey: 'unsubscribeSuccess-gameWall',
        descriptionParams: { gameTitle: 'Sonic the Hedgehog' },
        undoToken: null,
      },
    });

    // ASSERT
    expect(
      screen.getByText(/been unsubscribed from wall comments for Sonic the Hedgehog/i),
    ).toBeVisible();
  });

  it('given a descriptionKey but no descriptionParams, does not crash', () => {
    // ARRANGE
    const { container } = render(
      <UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />,
      {
        pageProps: {
          descriptionKey: 'unsubscribeSuccess-gameWall',
          descriptionParams: null,
          undoToken: 'some-undo-token',
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an undoToken, displays the undo button', () => {
    // ARRANGE
    render(<UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />, {
      pageProps: {
        descriptionKey: null,
        descriptionParams: null,
        undoToken: 'some-undo-token',
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /undo/i })).toBeVisible();
  });

  it('given no undoToken, does not display the undo button', () => {
    // ARRANGE
    render(<UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />, {
      pageProps: {
        descriptionKey: null,
        descriptionParams: null,
        undoToken: null,
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /undo/i })).not.toBeInTheDocument();
  });

  it('given isMutationPending is true, disables the undo button', () => {
    // ARRANGE
    render(<UnsubscribeSuccessCard isMutationPending={true} onUndo={vi.fn()} />, {
      pageProps: {
        descriptionKey: null,
        descriptionParams: null,
        undoToken: 'some-undo-token',
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /undo/i })).toBeDisabled();
  });

  it('given the user clicks the undo button, calls onUndo', async () => {
    // ARRANGE
    const onUndoSpy = vi.fn();

    render(<UnsubscribeSuccessCard isMutationPending={false} onUndo={onUndoSpy} />, {
      pageProps: {
        descriptionKey: null,
        descriptionParams: null,
        undoToken: 'some-undo-token',
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /undo/i }));

    // ASSERT
    expect(onUndoSpy).toHaveBeenCalledTimes(1);
  });

  it('displays the manage email preferences link with correct href', () => {
    // ARRANGE
    render(<UnsubscribeSuccessCard isMutationPending={false} onUndo={vi.fn()} />, {
      pageProps: {
        descriptionKey: null,
        descriptionParams: null,
        undoToken: null,
      },
    });

    // ASSERT
    const link = screen.getByRole('link', { name: /manage all email preferences/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', 'settings.show');
  });
});
