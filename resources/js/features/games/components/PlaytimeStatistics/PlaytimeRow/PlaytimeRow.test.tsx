import userEvent from '@testing-library/user-event';
import { LuStar } from 'react-icons/lu';

import { render, screen, waitFor } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { PlaytimeRow } from './PlaytimeRow';

describe('Component: PlaytimeRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the heading label', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Achievement Unlocked' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
      />,
    );

    // ASSERT
    expect(screen.getByText(/achievement unlocked/i)).toBeVisible();
  });

  it('given rowPlayers is provided, shows the player count', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        rowPlayers={42}
      />,
    );

    // ASSERT
    expect(screen.getByText(/42 players/i)).toBeVisible();
  });

  it('given both rowPlayers and totalPlayers are provided and different, shows the percentage', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        rowPlayers={25}
        totalPlayers={100}
      />,
    );

    // ASSERT
    expect(screen.getByText(/25 players \(25.0%\)/i)).toBeVisible();
  });

  it('given rowPlayers equals totalPlayers, does not show the percentage', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        rowPlayers={100}
        totalPlayers={100}
      />,
    );

    // ASSERT
    expect(screen.getByText(/100 players/i)).toBeVisible();
    expect(screen.queryByText(/%/)).not.toBeInTheDocument();
  });

  it('given totalSamples is 5 or more and rowSeconds is provided, shows the median time', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        rowSeconds={3600} // !! 1 hour.
        totalSamples={10}
      />,
    );

    // ASSERT
    expect(screen.getByText(/1h/i)).toBeVisible();
    expect(screen.getByText(/median time/i)).toBeVisible();
  });

  it('given totalSamples is less than 5, shows the "Not enough data" message', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        totalSamples={3} // !! not enough samples
      />,
    );

    // ASSERT
    expect(screen.getByText(/not enough data/i)).toBeVisible();
    expect(screen.queryByText(/median time/i)).not.toBeInTheDocument();
  });

  it('given totalSamples is less than 5 and the user hovers over the info icon, shows a tooltip with explanation', async () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        totalSamples={2} // !! not enough samples
      />,
    );

    // ACT
    await userEvent.hover(screen.getByText(/not enough data/i));

    // ASSERT
    await waitFor(() => {
      expect(
        screen.getAllByText(
          /not enough players have completed this milestone with time tracking enabled yet/i,
        )[0],
      ).toBeVisible();
    });
  });

  it('given no player data is provided, shows a 0 player count', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        rowPlayers={0}
      />,
    );

    // ASSERT
    expect(screen.getByText(/0 players/i)).toBeVisible();
  });

  it('given totalSamples is 5 or more but rowSeconds is not provided, does not show the median time label', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        totalSamples={10}
        rowSeconds={0}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/median time/i)).not.toBeInTheDocument();
  });

  it('given totalSamples is undefined, does not show the "Not enough data" message', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        totalSamples={undefined} // !! milestone not tracked for playtime
      />,
    );

    // ASSERT
    expect(screen.queryByText(/not enough data/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/median time/i)).not.toBeInTheDocument();
  });

  it('given totalSamples is 0, shows the "Not enough data" message', () => {
    // ARRANGE
    render(
      <PlaytimeRow
        headingLabel={'Test Heading' as TranslatedString}
        Icon={LuStar}
        iconContainerClassName="bg-blue-500"
        iconClassName="text-white"
        totalSamples={0} // !! no one has achieved this milestone yet
      />,
    );

    // ASSERT
    expect(screen.getByText(/not enough data/i)).toBeVisible();
    expect(screen.queryByText(/median time/i)).not.toBeInTheDocument();
  });
});
