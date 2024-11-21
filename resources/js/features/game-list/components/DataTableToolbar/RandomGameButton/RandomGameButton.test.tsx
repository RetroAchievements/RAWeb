import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';

import { RandomGameButton } from './RandomGameButton';

describe('Component: RandomGameButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <RandomGameButton columnFilters={[]} variant="toolbar" apiRouteName="api.game.random" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('on mount, immediately fetches a random game', () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: { gameId: 12345 } });

    render(
      <RandomGameButton
        columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
        variant="toolbar"
        apiRouteName="api.game.random"
      />,
    );

    // ASSERT
    expect(getSpy).toHaveBeenCalledOnce();
    expect(getSpy).toHaveBeenCalledWith([
      'api.game.random',
      { 'filter[achievementsPublished]': 'has' },
    ]);
  });

  it('on click, navigates the user', async () => {
    // ARRANGE
    const mockAssign = vi.fn();
    delete (window as any).location;
    window.location = { assign: mockAssign } as any;

    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: { gameId: 12345 } });

    render(
      <RandomGameButton
        columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
        variant="toolbar"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /surprise me/i }));

    // ASSERT
    expect(mockAssign).toHaveBeenCalledWith(['game.show', 12345]);
  });

  it('also supports a mobile drawer variant', async () => {
    // ARRANGE
    const mockAssign = vi.fn();
    delete (window as any).location;
    window.location = { assign: mockAssign } as any;

    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: { gameId: 12345 } });

    render(
      <RandomGameButton
        columnFilters={[{ id: 'achievementsPublished', value: 'has' }]}
        variant="mobile-drawer"
        apiRouteName="api.user-game-list.random"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /surprise me/i }));

    // ASSERT
    expect(mockAssign).toHaveBeenCalledWith(['game.show', 12345]);
  });
});
