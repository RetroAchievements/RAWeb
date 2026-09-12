import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createAchievementSetClaim, createUser } from '@/test/factories';

import { MarkClaimPartCompleteDialog } from './MarkClaimPartCompleteDialog';

describe('Component: MarkClaimPartCompleteDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MarkClaimPartCompleteDialog
        claims={[createAchievementSetClaim()]}
        trigger={<button>Trigger</button>}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the trigger is clicked, opens the dialog with the primary claimant listed first', async () => {
    // ARRANGE
    render(
      <MarkClaimPartCompleteDialog
        claims={[
          createAchievementSetClaim({
            claimType: 'collaboration',
            user: createUser({ displayName: 'Bob' }),
          }),
          createAchievementSetClaim({
            claimType: 'primary',
            user: createUser({ displayName: 'Alice' }),
          }),
        ]}
        trigger={<button>Trigger</button>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Mark claim part complete?' })).toBeVisible();

    const options = screen.getAllByRole('option');
    expect(options.map((option) => option.textContent)).toEqual([
      'Select a claimant',
      'Alice (Primary)',
      'Bob (Collaboration)',
    ]);
  });

  it('disables the confirm button until a claimant is selected, and enables it once the user selects someone', async () => {
    // ARRANGE
    render(
      <MarkClaimPartCompleteDialog
        claims={[createAchievementSetClaim({ user: createUser({ displayName: 'Alice' }) })]}
        trigger={<button>Trigger</button>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    const confirmButton = screen.getByRole('button', { name: 'Mark part complete' });
    expect(confirmButton).toBeDisabled();

    await userEvent.selectOptions(
      screen.getByRole('combobox'),
      screen.getByRole('option', { name: /Alice/ }),
    );
    expect(confirmButton).toBeEnabled();
  });

  it('given a claimant is selected and confirmed, makes a POST call for that claim and then reloads the page client-side', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());

    render(
      <MarkClaimPartCompleteDialog
        claims={[
          createAchievementSetClaim({ id: 101, user: createUser({ displayName: 'Alice' }) }),
          createAchievementSetClaim({ id: 102, user: createUser({ displayName: 'Bob' }) }),
        ]}
        trigger={<button>Trigger</button>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.selectOptions(
      screen.getByRole('combobox'),
      screen.getByRole('option', { name: /Bob/ }),
    );
    await userEvent.click(screen.getByRole('button', { name: 'Mark part complete' }));

    // ASSERT
    await waitFor(() => {
      expect(reloadSpy).toHaveBeenCalledOnce();
    });

    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith(
      route('achievement-set-claim.release-scheduled', { claim: 102 }),
    );
  });

  it('given the dialog is closed and reopened, clears the previous selection', async () => {
    // ARRANGE
    render(
      <MarkClaimPartCompleteDialog
        claims={[createAchievementSetClaim({ user: createUser({ displayName: 'Alice' }) })]}
        trigger={<button>Trigger</button>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.selectOptions(
      screen.getByRole('combobox'),
      screen.getByRole('option', { name: /Alice/ }),
    );
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveValue('');
    expect(screen.getByRole('button', { name: 'Mark part complete' })).toBeDisabled();
  });
});
