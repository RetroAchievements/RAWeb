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
          unlockHardcorePercentage: '0.5',
        })}
        playersTotal={200}
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
          unlockHardcorePercentage: '0.5',
        })}
        playersTotal={200}
      />,
    );

    // ASSERT
    expect(screen.getByText('100')).toBeVisible();
    expect(screen.getByText('(50)')).toBeVisible();
    expect(screen.getByText('200')).toBeVisible();
    expect(screen.getByText('- 50.00%')).toBeVisible();
  });

  it('given hardcore unlocks equal total unlocks, hides the hardcore count with sr-only', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 75,
          unlocksHardcoreTotal: 75,
          unlockHardcorePercentage: '0.375',
        })}
        playersTotal={200}
      />,
    );

    // ASSERT
    const hardcoreElement = screen.getByText('(75)');
    expect(hardcoreElement).toHaveClass('sr-only');
  });

  it('given hardcore unlocks equal total unlocks and are greater than zero, makes the total count bold', () => {
    // ARRANGE
    render(
      <ProgressBarMetaText
        achievement={createAchievement({
          unlocksTotal: 75,
          unlocksHardcoreTotal: 75,
          unlockHardcorePercentage: '0.375',
        })}
        playersTotal={200}
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
          unlockHardcorePercentage: '0.5',
        })}
        playersTotal={200}
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
          unlockHardcorePercentage: '0.0',
        })}
        playersTotal={0}
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
          unlockHardcorePercentage: undefined,
        })}
        playersTotal={200}
      />,
    );

    // ASSERT
    expect(screen.getByText('0')).toBeVisible();
    expect(screen.getByText('(0)')).toBeVisible();
    expect(screen.getByText('200')).toBeVisible();
  });
});
