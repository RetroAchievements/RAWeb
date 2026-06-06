import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { ScreenshotSlotStatusIndicator } from './ScreenshotSlotStatusIndicator';

describe('Component: ScreenshotSlotStatusIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ScreenshotSlotStatusIndicator />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no typeStatus is provided, shows a "Needed" label', () => {
    // ARRANGE
    render(<ScreenshotSlotStatusIndicator />);

    // ASSERT
    expect(screen.getByText(/needed/i)).toBeVisible();
  });

  it('given a typeStatus with resolution issues, shows a warning icon with a tooltip describing the issue', async () => {
    // ARRANGE
    render(<ScreenshotSlotStatusIndicator typeStatus={{ count: 1, hasResolutionIssues: true }} />);

    // ACT
    await userEvent.hover(screen.getByRole('img', { name: /warning/i }));

    // ASSERT
    expect(screen.queryByText(/needed/i)).not.toBeInTheDocument();
    expect(
      await screen.findByRole('tooltip', { name: /incorrect resolution/i }),
    ).toBeInTheDocument();
  });

  it('given a typeStatus with no resolution issues, does not show a "Needed" label or a warning', () => {
    // ARRANGE
    render(<ScreenshotSlotStatusIndicator typeStatus={{ count: 1, hasResolutionIssues: false }} />);

    // ASSERT
    expect(screen.queryByText(/needed/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });
});
