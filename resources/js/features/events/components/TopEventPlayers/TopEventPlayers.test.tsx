import { render, screen } from '@/test';
import {
  createAchievement,
  createEventAchievement,
  createGame,
  createGameTopAchiever,
  createRaEvent,
} from '@/test/factories';

import { TopEventPlayers } from './TopEventPlayers';

describe('Component: TopEventPlayers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TopEventPlayers
        event={createRaEvent()}
        numMasters={5}
        players={[createGameTopAchiever()]}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no players, returns null', () => {
    // ARRANGE
    render(<TopEventPlayers event={createRaEvent()} numMasters={0} players={[]} />);

    // ASSERT
    expect(screen.queryByTestId('top-players')).not.toBeInTheDocument();
  });

  it('given there are more than 10 masters, shows Latest Masters list', () => {
    // ARRANGE
    render(
      <TopEventPlayers
        event={createRaEvent()}
        numMasters={11}
        players={[createGameTopAchiever()]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/latest masters/i)).toBeVisible();
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('given all achievements are worth 1 point and there are less than 10 masters, shows Most Achievements Earned list', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 1 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 1 }) }),
      ],
    });

    render(<TopEventPlayers event={event} numMasters={5} players={[createGameTopAchiever()]} />);

    // ASSERT
    expect(screen.getByText(/most achievements earned/i)).toBeVisible();
    expect(screen.getByRole('cell', { name: /achievements/i })).toBeVisible();
  });

  it('given some achievements are worth more than 1 point and there are less than 10 masters, shows Most Points Earned list', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 1 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
      ],
    });

    render(<TopEventPlayers event={event} numMasters={5} players={[createGameTopAchiever()]} />);

    // ASSERT
    expect(screen.getByText(/most points earned/i)).toBeVisible();
    expect(screen.getByRole('cell', { name: /points/i })).toBeVisible();

    expect(screen.queryByRole('cell', { name: /achievements/i })).not.toBeInTheDocument();
  });

  it('given there are more than 10 players and a legacy game ID, shows the See more link', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({ id: 123, playersHardcore: 11 }),
    });

    render(<TopEventPlayers event={event} numMasters={5} players={[createGameTopAchiever()]} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /see more/i })).toBeVisible();
  });

  it('given there are 10 or fewer players, does not show the See more link', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({ id: 123, playersHardcore: 10 }),
    });

    render(<TopEventPlayers event={event} numMasters={5} players={[createGameTopAchiever()]} />);

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

    const event = createRaEvent({
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
      ],
    });

    render(<TopEventPlayers event={event} numMasters={5} players={players} />);

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

    const event = createRaEvent({
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
      ],
    });

    render(<TopEventPlayers event={event} numMasters={5} players={players} />);

    // ASSERT
    const rows = screen.getAllByRole('row');

    // ... skip the header row ...
    expect(rows[1]).toHaveTextContent('1');
    expect(rows[2]).toHaveTextContent('1');
    expect(rows[3]).toHaveTextContent('3');
  });
});
