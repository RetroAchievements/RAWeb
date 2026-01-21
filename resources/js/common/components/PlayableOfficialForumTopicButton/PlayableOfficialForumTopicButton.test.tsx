import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { PlayableOfficialForumTopicButton } from './PlayableOfficialForumTopicButton';

// Suppress expected error logs.
console.error = vi.fn();

describe('Component: OfficialForumTopicButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    const { container } = render(<PlayableOfficialForumTopicButton game={game} />, {
      pageProps: { can: {} },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has no forum topic, does not show a link', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: undefined });

    render(<PlayableOfficialForumTopicButton game={game} />, {
      pageProps: { can: { createGameForumTopic: false } },
    });

    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given the backing game has a forum topic, shows a link to it', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: 123 });

    render(
      <PlayableOfficialForumTopicButton
        game={game}
        backingGame={createGame({ id: 1, forumTopicId: 456 })}
      />,
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /official forum topic/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('forum-topic.show'));
  });

  it('given viewing a subset where both game entities have forum topics, shows both links', () => {
    // ARRANGE
    const baseGame = createGame({ id: 1, forumTopicId: 456 });
    const subsetGame = createGame({ id: 2, forumTopicId: 789 });

    render(<PlayableOfficialForumTopicButton game={baseGame} backingGame={subsetGame} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /game forum topic/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /subset forum topic/i })).toBeVisible();
  });

  it('given viewing a subset where only the "subset game" has a forum topic, shows only the subset forum topic link', () => {
    // ARRANGE
    const baseGame = createGame({ id: 1, forumTopicId: undefined });
    const subsetGame = createGame({ id: 2, forumTopicId: 789 });

    render(<PlayableOfficialForumTopicButton game={baseGame} backingGame={subsetGame} />);

    // ASSERT
    expect(screen.queryByRole('link', { name: /game forum topic/i })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: /subset forum topic/i })).toBeVisible();
  });

  it('given viewing a subset where only the base game has a forum topic, shows only the game forum topic link', () => {
    // ARRANGE
    const baseGame = createGame({ id: 1, forumTopicId: 456 });
    const subsetGame = createGame({ id: 2, forumTopicId: undefined });

    render(<PlayableOfficialForumTopicButton game={baseGame} backingGame={subsetGame} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /game forum topic/i })).toBeVisible();
    expect(screen.queryByRole('link', { name: /subset forum topic/i })).not.toBeInTheDocument();
  });
});
