import { render, screen, waitFor } from '@/test';

import { GameListSection } from './GameListSection';

describe('Component: GameListSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2}>
        children
      </GameListSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided title', async () => {
    // ARRANGE
    render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2}>
        children
      </GameListSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/title/i)).toBeVisible();
    });
  });

  it('displays children', { retry: 3 }, async () => {
    // ARRANGE
    render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2}>
        children
      </GameListSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });

  it('displays the count of games', () => {
    // ARRANGE
    render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2}>
        children
      </GameListSection>,
    );

    // ASSERT
    expect(screen.getByText(/2 games/i)).toBeInTheDocument();
  });

  it('displays the count of mastered games', () => {
    // ARRANGE
    render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2} masteredCount={1}>
        children
      </GameListSection>,
    );

    // ASSERT
    expect(screen.getByText(/2 games - 1 mastered/i)).toBeInTheDocument();
  });

  it('displays the count of beaten games', () => {
    // ARRANGE
    render(
      <GameListSection title="title" isInitiallyOpened={true} gameCount={2} beatenCount={1}>
        children
      </GameListSection>,
    );

    // ASSERT
    expect(screen.getByText(/2 games - 1 beaten/i)).toBeInTheDocument();
  });

  it('displays the count of mastered and beaten games', () => {
    // ARRANGE
    render(
      <GameListSection
        title="title"
        isInitiallyOpened={true}
        gameCount={2}
        masteredCount={1}
        beatenCount={1}
      >
        children
      </GameListSection>,
    );

    // ASSERT
    expect(screen.getByText(/2 games - 1 mastered, 1 beaten/i)).toBeInTheDocument();
  });

  it('displays the count of mastered, beaten, completed, and beaten (softcore) games', () => {
    // ARRANGE
    render(
      <GameListSection
        title="title"
        isInitiallyOpened={true}
        gameCount={15}
        masteredCount={4}
        beatenCount={3}
        completedCount={2}
        beatenSoftcoreCount={1}
      >
        children
      </GameListSection>,
    );

    // ASSERT
    expect(
      screen.getByText(/15 games - 4 mastered, 3 beaten, 2 completed, 1 beaten \(softcore\)/i),
    ).toBeInTheDocument();
  });

  it('given the section is initially closed, applies the hidden class', () => {
    // ACT
    render(
      <GameListSection title="title" isInitiallyOpened={false} gameCount={2}>
        <div data-testid="child-content">children</div>
      </GameListSection>,
    );

    // ASSERT
    const contentDiv = screen.getByText(/children/i).closest('div[class*="overflow-hidden"]');
    expect(contentDiv).toHaveClass('h-0');
    expect(contentDiv).toHaveClass('overflow-hidden');
  });
});
