import { render, screen } from '@/test';
import { createAchievement, createGame, createGameTopAchiever } from '@/test/factories';

import { PlayableTopPlayers } from './PlayableTopPlayers';

describe('Component: PlayableTopPlayers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlayableTopPlayers
        achievements={[]}
        game={createGame()}
        numMasters={5}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no players, returns null', () => {
    // ARRANGE
    render(
      <PlayableTopPlayers
        achievements={[]}
        game={createGame()}
        numMasters={0}
        players={[]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('top-players')).not.toBeInTheDocument();
  });

  it('given there are more than 10 masters, shows Latest Masters list', () => {
    // ARRANGE
    render(
      <PlayableTopPlayers
        achievements={[]}
        game={createGame()}
        numMasters={11}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByText(/latest masters/i)).toBeVisible();
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('given all achievements are worth 1 point and there are less than 10 masters, shows Most Achievements Earned list', () => {
    // ARRANGE
    const achievements = [createAchievement({ points: 1 }), createAchievement({ points: 1 })];

    render(
      <PlayableTopPlayers
        achievements={achievements}
        game={createGame()}
        numMasters={5}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByText(/most achievements earned/i)).toBeVisible();
    expect(screen.getByRole('cell', { name: /achievements/i })).toBeVisible();
  });

  it('given some achievements are worth more than 1 point and there are less than 10 masters, shows Most Points Earned list', () => {
    // ARRANGE
    const achievements = [createAchievement({ points: 1 }), createAchievement({ points: 5 })];

    render(
      <PlayableTopPlayers
        achievements={achievements}
        game={createGame()}
        numMasters={5}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByText(/most points earned/i)).toBeVisible();
    expect(screen.getByRole('cell', { name: /points/i })).toBeVisible();

    expect(screen.queryByRole('cell', { name: /achievements/i })).not.toBeInTheDocument();
  });

  it('given there are more than 10 players and a legacy game ID, shows the See more link', () => {
    // ARRANGE
    const game = createGame({ id: 123, playersHardcore: 11 });

    render(
      <PlayableTopPlayers
        achievements={[]}
        game={game}
        numMasters={5}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /see more/i })).toBeVisible();
  });

  it('given there are 10 or fewer players, does not show the See more link', () => {
    // ARRANGE
    const game = createGame({ id: 123, playersHardcore: 10 });

    render(
      <PlayableTopPlayers
        achievements={[]}
        game={game}
        numMasters={5}
        players={[createGameTopAchiever()]}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.queryByRole('link', { name: /see more/i })).not.toBeInTheDocument();
  });

  it('given multiple players with different points, assigns ranks correctly', () => {
    // ARRANGE
    const players = [
      createGameTopAchiever({ pointsHardcore: 100 }),
      createGameTopAchiever({ pointsHardcore: 75 }),
      createGameTopAchiever({ pointsHardcore: 50 }),
    ];

    const game = createGame();
    const achievements = [createAchievement({ points: 5 })];

    render(
      <PlayableTopPlayers
        achievements={achievements}
        game={game}
        numMasters={5}
        players={players}
        variant="event"
      />,
    );

    // ASSERT
    const rows = screen.getAllByRole('row');

    // ... skip the header row ...
    expect(rows[1]).toHaveTextContent('1');
    expect(rows[2]).toHaveTextContent('2');
    expect(rows[3]).toHaveTextContent('3');
  });

  it('given multiple players with tied points, assigns the same rank', () => {
    // ARRANGE
    const players = [
      createGameTopAchiever({ pointsHardcore: 100 }),
      createGameTopAchiever({ pointsHardcore: 100 }),
      createGameTopAchiever({ pointsHardcore: 50 }),
    ];

    const achievements = [createAchievement({ points: 5 })];
    const game = createGame();

    render(
      <PlayableTopPlayers
        achievements={achievements}
        game={game}
        numMasters={5}
        players={players}
        variant="event"
      />,
    );

    // ASSERT
    const rows = screen.getAllByRole('row');

    // ... skip the header row ...
    expect(rows[1]).toHaveTextContent('1');
    expect(rows[2]).toHaveTextContent('1');
    expect(rows[3]).toHaveTextContent('3');
  });

  it('given the game variant, correctly shows mastery award indicators', () => {
    // ARRANGE
    const players = [
      createGameTopAchiever({ achievementsUnlockedHardcore: 6 }), // !! 6
      createGameTopAchiever({
        achievementsUnlockedHardcore: 1,
        beatenHardcoreAt: new Date().toISOString(), // !! beaten, but not mastered
      }),
      createGameTopAchiever({ achievementsUnlockedHardcore: 1 }),
    ];

    // ... six of these ...
    const achievements = [
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
    ];

    const game = createGame();

    render(
      <PlayableTopPlayers
        achievements={achievements}
        game={game}
        numMasters={1}
        players={players}
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('img', { name: /mastered/i }).length).toEqual(1);
    expect(screen.getAllByRole('img', { name: /beaten/i }).length).toEqual(1);
  });
});
