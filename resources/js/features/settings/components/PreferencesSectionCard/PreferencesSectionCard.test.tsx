import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { PreferencesSectionCard } from './PreferencesSectionCard';

describe('Component: PreferencesSectionCard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PreferencesSectionCard currentPreferencesBitfield={131200} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('correctly sets the initial form values', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentPreferencesBitfield={131200} />);

    // ASSERT
    expect(screen.getByRole('switch', { name: /suppress mature content warnings/i })).toBeChecked();
    expect(screen.getByRole('switch', { name: /prefer absolute dates/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /hide missable/i })).not.toBeChecked();
    expect(screen.getByRole('switch', { name: /only people i follow/i })).toBeChecked();
  });

  it('given the user submits the form, makes the correct request to the server', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard currentPreferencesBitfield={139471} />);

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /only people i follow/i }));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      preferencesBitfield: 8399,
    });
  });

  it('given the user does not have the game subsets opt out setting enabled, shows the toggle as checked', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentPreferencesBitfield={127} />);

    // ASSERT
    const switchEl = screen.getByRole('switch', { name: /automatically opt in/i });

    expect(switchEl).toBeChecked();
  });

  it('allows the user to change their game subsets opt out preference', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render(<PreferencesSectionCard currentPreferencesBitfield={127} />);

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /automatically opt in/i }));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      preferencesBitfield: 262271,
    });
  });

  it('given the user has beta features enabled, shows the beta features toggle as checked', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentPreferencesBitfield={532725} />);

    // ASSERT
    expect(screen.getByRole('switch', { name: /enable beta features/i })).toBeChecked();
  });

  it('given the user does not have beta features enabled, shows the beta features toggle as unchecked', () => {
    // ARRANGE
    render(<PreferencesSectionCard currentPreferencesBitfield={0} />);

    // ASSERT
    expect(screen.getByRole('switch', { name: /enable beta features/i })).not.toBeChecked();
  });
});
