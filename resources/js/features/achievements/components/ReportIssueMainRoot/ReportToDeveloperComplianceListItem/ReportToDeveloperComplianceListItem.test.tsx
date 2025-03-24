import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { ReportToDeveloperComplianceListItem } from './ReportToDeveloperComplianceListItem';

describe('Component: ReportToDeveloperComplianceListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ReportToDeveloperComplianceListItem achievement={createAchievement()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the achievement is from a non-subset game, renders the regular report option item', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({
        isSubsetGame: false,
      }),
    });

    render(<ReportToDeveloperComplianceListItem achievement={achievement} />);

    // ASSERT
    expect(screen.getByText(/the achievement contains an/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /report to devcompliance/i })).toBeVisible();
    expect(
      screen.queryByRole('button', { name: /report to devcompliance/i }),
    ).not.toBeInTheDocument();
  });

  it('given the achievement is from a subset game, does not initially hyperlink to create a message', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({
        isSubsetGame: true,
      }),
    });

    render(<ReportToDeveloperComplianceListItem achievement={achievement} />);

    // ASSERT
    expect(screen.getByText(/the achievement contains an/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /report to devcompliance/i })).toBeVisible();
    expect(
      screen.queryByRole('link', { name: /report to devcompliance/i }),
    ).not.toBeInTheDocument();
  });

  it('given the achievement is from a subset game, shows an alert dialog on button click', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({
        isSubsetGame: true,
      }),
    });

    render(<ReportToDeveloperComplianceListItem achievement={achievement} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /report to devcompliance/i }));

    // ASSERT
    expect(screen.getByRole('heading', { name: /are you sure\?/i })).toBeVisible();
    expect(screen.getByText(/this achievement appears to be part of a subset/i)).toBeVisible();
    expect(screen.getByRole('button', { name: /nevermind/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /continue/i })).toBeVisible();
  });

  it('given the alert dialog is open, navigates to the message page on click', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({
        isSubsetGame: true,
      }),
    });

    const mockWindowLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockWindowLocationAssign },
      writable: true,
    });

    render(<ReportToDeveloperComplianceListItem achievement={achievement} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /report to devcompliance/i }));
    await userEvent.click(screen.getByRole('button', { name: /continue/i }));

    // ASSERT
    expect(mockWindowLocationAssign).toHaveBeenCalledWith([
      'message-thread.create',
      expect.anything(),
    ]);
  });
});
