import userEvent from '@testing-library/user-event';

import { BaseTooltipProvider } from '@/common/components/+vendor/BaseTooltip';
import { render, screen, waitFor } from '@/test';
import { createGameHash, createPlayerGameActivitySession } from '@/test/factories';

import { HashLabel } from './HashLabel';

describe('Component: HashLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseTooltipProvider>
        <HashLabel session={createPlayerGameActivitySession()} />
      </BaseTooltipProvider>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the session has no game hash, shows the unknown hash message', () => {
    // ARRANGE
    render(
      <BaseTooltipProvider>
        <HashLabel session={createPlayerGameActivitySession({ gameHash: null })} />
      </BaseTooltipProvider>,
    );

    // ASSERT
    expect(screen.getByText(/unknown hash/i)).toBeVisible();
  });

  it('given the session hash has no name, shows only the hash md5', () => {
    // ARRANGE
    render(
      <BaseTooltipProvider>
        <HashLabel
          session={createPlayerGameActivitySession({
            gameHash: createGameHash({ name: null, md5: '123412341234' }),
          })}
        />
      </BaseTooltipProvider>,
    );

    // ASSERT
    expect(screen.getByText('123412341234')).toBeVisible();
  });

  it('given the session hash has a name, shows it with an md5 tooltip', async () => {
    // ARRANGE
    render(
      <BaseTooltipProvider>
        <HashLabel
          session={createPlayerGameActivitySession({
            gameHash: createGameHash({ name: 'Final Fantasy III (USA)', md5: '123412341234' }),
          })}
        />
      </BaseTooltipProvider>,
    );

    // ACT
    await userEvent.hover(screen.getByText(/final fantasy iii/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('123412341234')[0]).toBeVisible();
    });
  });

  it('given the session hash is from a multi-disc game, shows an indicator', () => {
    // ARRANGE
    render(
      <BaseTooltipProvider>
        <HashLabel
          session={createPlayerGameActivitySession({
            gameHash: createGameHash({
              name: 'Final Fantasy VII (USA) (Disc 1)',
              md5: '123412341234',
              isMultiDisc: true,
            }),
          })}
        />
      </BaseTooltipProvider>,
    );

    // ASSERT
    expect(screen.getByText(/this is a game with multiple discs/i)).toBeVisible();
  });
});
