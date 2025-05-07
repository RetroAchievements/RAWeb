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

  it('given the game has a forum topic, shows a link to it', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: 123 });

    render(<PlayableOfficialForumTopicButton game={game} />);

    // ASSERT
    const link = screen.getByRole('link', { name: /official forum topic/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('forum-topic.show'));
  });
});
