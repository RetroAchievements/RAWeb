import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { ProgressBarMetaText } from './ProgressBarMetaText';

describe('Component: ProgressBarMetaText', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="event"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given achievement data, displays the unlock stats correctly', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByText('100')).toBeVisible();
    expect(screen.getByText('(50)')).toBeVisible();
    expect(screen.getByText('200')).toBeVisible();
    expect(screen.getByText('- 50.00%')).toBeVisible();
  });

  it('given hardcore unlocks equal total unlocks and variant is event, hides the hardcore count with sr-only', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 75,
          unlocksHardcoreTotal: 75, // !! equal counts
          unlockPercentage: '0.375',
        })}
        playersTotal={200}
        variant="event" // !!
      />,
    );

    // ASSERT
    const hardcoreElement = screen.getByText('(75)');
    expect(hardcoreElement).toHaveClass('sr-only');
  });

  it('given hardcore unlocks equal total unlocks and variant is game, shows the hardcore count without sr-only', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 75,
          unlocksHardcoreTotal: 75, // !! equal counts
          unlockPercentage: '0.375',
        })}
        playersTotal={200}
        variant="game" // !!
      />,
    );

    // ASSERT
    const hardcoreElement = screen.getByText('(75)');
    expect(hardcoreElement).not.toHaveClass('sr-only');
    expect(hardcoreElement).toHaveClass('font-bold');
  });

  it('given hardcore unlocks equal total unlocks and are greater than zero, makes the total count bold', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 75,
          unlocksHardcoreTotal: 75,
          unlockPercentage: '0.375',
        })}
        playersTotal={200}
        variant="event"
      />,
    );

    // ASSERT
    const totalElement = screen.getByText('75');
    expect(totalElement).toHaveClass('font-bold');
  });

  it('given hardcore unlocks do not equal total unlocks, does not make the total count bold', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="event"
      />,
    );

    // ASSERT
    const totalElement = screen.getByText('100');
    expect(totalElement).not.toHaveClass('font-bold');
  });

  it('given all count values are zero or null, handles them gracefully', () => {
    // ARRANGE
    const { container } = render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 0,
          unlocksHardcoreTotal: 0,
          unlockPercentage: '0.0',
        })}
        playersTotal={0}
        variant="event"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByText('(0)')).toBeVisible();
    expect(screen.getByText('0', { selector: 'span[title="Total players"]' })).toBeVisible();
  });

  it('given nullable achievement values, defaults them to zero', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: undefined,
          unlocksHardcoreTotal: undefined,
          unlockPercentage: undefined,
        })}
        playersTotal={200}
        variant="event"
      />,
    );

    // ASSERT
    expect(screen.getByText('0')).toBeVisible();
    expect(screen.getByText('(0)')).toBeVisible();
    expect(screen.getByText('200')).toBeVisible();
  });

  it('given shouldPrioritizeHardcoreStats is true, displays the hardcore unlock count as the main count', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50, // !! should show this as the main count
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="game"
        shouldPrioritizeHardcoreStats={true} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('50')).toBeVisible();
    expect(screen.queryByText('100')).not.toBeInTheDocument(); // !! total count not shown
  });

  it('given shouldPrioritizeHardcoreStats is true, hides the hardcore count in parentheses', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50,
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="game"
        shouldPrioritizeHardcoreStats={true} // !!
      />,
    );

    // ASSERT
    const hardcoreElement = screen.getByText('(50)');
    expect(hardcoreElement).toHaveClass('sr-only');
  });

  it('given shouldPrioritizeHardcoreStats is true, calculates the percentage from hardcore unlocks divided by total players', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100,
          unlocksHardcoreTotal: 50, // 50/200 = 0.25 = 25%
          unlockPercentage: '0.5', // this should be ignored
        })}
        playersTotal={200} // !!
        variant="game"
        shouldPrioritizeHardcoreStats={true} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('- 25.00%')).toBeVisible(); // !! 50/200
  });

  it('given shouldPrioritizeHardcoreStats is false, displays total unlock count as the main count', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 100, // should show this as main count
          unlocksHardcoreTotal: 50,
          unlockPercentage: '0.5',
        })}
        playersTotal={200}
        variant="game"
        shouldPrioritizeHardcoreStats={false} // !!
      />,
    );

    // ASSERT
    expect(screen.getByText('100')).toBeVisible();
    expect(screen.getByText('(50)')).toBeVisible();
  });
});
