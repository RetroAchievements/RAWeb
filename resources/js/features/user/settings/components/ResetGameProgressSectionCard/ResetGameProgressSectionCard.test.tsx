import axios from 'axios';
import {
  mockAllIsIntersecting,
  resetIntersectionMocking,
} from 'react-intersection-observer/test-utils';

import { render } from '@/test';
import { createPlayerResettableGame } from '@/test/factories';

import { ResetGameProgressSectionCard } from './ResetGameProgressSectionCard';

describe('Component: ResetGameProgressSectionCard', () => {
  afterEach(() => {
    resetIntersectionMocking();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ResetGameProgressSectionCard />);

    mockAllIsIntersecting(false);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it("given the component has never been on-screen, does not make an API call to fetch the player's resettable games", () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get');

    render(<ResetGameProgressSectionCard />);

    mockAllIsIntersecting(false);

    // ASSERT
    expect(getSpy).not.toHaveBeenCalled();
  });

  it("given the component is visible, fetches the player's resettable games", () => {
    // ARRANGE
    const getSpy = vi
      .spyOn(axios, 'get')
      .mockResolvedValueOnce({ results: [createPlayerResettableGame()] });

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    // ASSERT
    expect(getSpy).toHaveBeenCalledWith(route('player.games.resettable'));
  });

  it.todo('given the user selects a game, fetches the resettable achievements');
  it.todo(
    'given the user wants to reset all achievements, sends the correct request to the server',
  );
  it.todo(
    'given the user wants to reset an individual achievement, sends the correct request to the server',
  );
  it.todo('after resetting a game, removes it from the list of selectable games');
  it.todo('after resetting an achievement, removes it from the list of selectable achievements');
});
