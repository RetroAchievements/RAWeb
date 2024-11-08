import userEvent from '@testing-library/user-event';
import axios from 'axios';
import nock from 'nock';
import {
  mockAllIsIntersecting,
  resetIntersectionMocking,
} from 'react-intersection-observer/test-utils';

import { render, screen, waitFor } from '@/test';
import {
  createPlayerResettableGame,
  createPlayerResettableGameAchievement,
} from '@/test/factories';

import { ResetGameProgressSectionCard } from './ResetGameProgressSectionCard';

describe('Component: ResetGameProgressSectionCard', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

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

  it("given the component is visible, fetches the player's resettable games", async () => {
    // ARRANGE
    nock('http://localhost:3000')
      .get('/player.games.resettable')
      .reply(200, { results: [createPlayerResettableGame({ title: 'Sonic the Hedgehog' })] });

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    // ASSERT
    await waitFor(() => {
      expect(
        screen.getByRole('option', { name: /sonic the hedgehog/i, hidden: true }),
      ).toBeInTheDocument();
    });
  });

  it('given the user selects a resettable game, fetches resettable achievements', async () => {
    // ARRANGE
    nock('http://localhost:3000')
      .get('/player.games.resettable')
      .reply(200, {
        results: [createPlayerResettableGame({ id: 1, title: 'Sonic the Hedgehog' })],
      });

    nock('http://localhost:3000')
      .get('/player.game.achievements.resettable,1')
      .reply(200, {
        results: [
          createPlayerResettableGameAchievement({
            title: 'That Was Easy!',
            points: 5,
            isHardcore: true,
          }),
        ],
      });

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    await userEvent.click(screen.getByRole('combobox', { name: /game/i }));
    await userEvent.click(await screen.findByRole('option', { name: /sonic the hedgehog/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /achievement/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('option', { name: /that was easy/i })).toBeInTheDocument();
    });
  });

  it('given the user wants to reset all achievements, sends the correct request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValueOnce(true);

    nock('http://localhost:3000')
      .get('/player.games.resettable')
      .reply(200, {
        results: [createPlayerResettableGame({ id: 1, title: 'Sonic the Hedgehog' })],
      });

    nock('http://localhost:3000')
      .get('/player.game.achievements.resettable,1')
      .reply(200, {
        results: [
          createPlayerResettableGameAchievement({
            title: 'That Was Easy!',
            points: 5,
            isHardcore: true,
          }),
        ],
      });

    nock('http://localhost:3000').delete('/api.user.game.destroy,1').reply(200);

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    await userEvent.click(screen.getByRole('combobox', { name: /game/i }));
    await userEvent.click(await screen.findByRole('option', { name: /sonic the hedgehog/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /achievement/i }));
    await userEvent.click(screen.getByRole('option', { name: /all won achievements/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    await waitFor(() => {
      // The option should've been optimistically removed from the list.
      expect(
        screen.queryByRole('option', { name: /sonic the hedgehog/i, hidden: true }),
      ).not.toBeInTheDocument();
    });

    expect(await screen.findByText(/progress was reset/i)).toBeVisible();
  });

  it('given the user wants to reset an individual achievement, sends the correct request to the server', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValueOnce(true);

    nock('http://localhost:3000')
      .get('/player.games.resettable')
      .reply(200, {
        results: [createPlayerResettableGame({ id: 1, title: 'Sonic the Hedgehog' })],
      });

    nock('http://localhost:3000')
      .get('/player.game.achievements.resettable,1')
      .reply(200, {
        results: [
          createPlayerResettableGameAchievement({
            id: 9,
            title: 'That Was Easy!',
            points: 5,
            isHardcore: true,
          }),
        ],
      });

    nock('http://localhost:3000').delete('/api.user.achievement.destroy,9').reply(200);

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    await userEvent.click(screen.getByRole('combobox', { name: /game/i }));
    await userEvent.click(await screen.findByRole('option', { name: /sonic the hedgehog/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /achievement/i }));
    await userEvent.click(screen.getByRole('option', { name: /that was easy/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    await waitFor(() => {
      // The option should've been optimistically removed from the list.
      expect(
        screen.queryByRole('option', { name: /that was easy/i, hidden: true }),
      ).not.toBeInTheDocument();
    });

    expect(await screen.findByText(/progress was reset/i)).toBeVisible();
  });

  it('given the user does not confirm the prompt to reset progress, does not send a request to the server', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete');

    vi.spyOn(window, 'confirm').mockReturnValueOnce(false);

    nock('http://localhost:3000')
      .get('/player.games.resettable')
      .reply(200, {
        results: [createPlayerResettableGame({ id: 1, title: 'Sonic the Hedgehog' })],
      });

    nock('http://localhost:3000')
      .get('/player.game.achievements.resettable,1')
      .reply(200, {
        results: [
          createPlayerResettableGameAchievement({
            id: 9,
            title: 'That Was Easy!',
            points: 5,
            isHardcore: false,
          }),
        ],
      });

    render(<ResetGameProgressSectionCard />);

    // ACT
    mockAllIsIntersecting(true);

    await userEvent.click(screen.getByRole('combobox', { name: /game/i }));
    await userEvent.click(await screen.findByRole('option', { name: /sonic the hedgehog/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /achievement/i }));
    await userEvent.click(screen.getByRole('option', { name: /that was easy/i }));

    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
  });
});
