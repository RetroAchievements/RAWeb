import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createZiggyProps } from '@/test/factories';

import {
  ResponsiveTooltip,
  ResponsiveTooltipContent,
  ResponsiveTooltipTrigger,
} from './ResponsiveTooltip';

describe('Component: ResponsiveTooltip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ResponsiveTooltip>
        <ResponsiveTooltipTrigger>Trigger</ResponsiveTooltipTrigger>
        <ResponsiveTooltipContent>Content</ResponsiveTooltipContent>
      </ResponsiveTooltip>,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  describe('Desktop behavior', () => {
    it('renders the trigger element', () => {
      // ARRANGE
      render(
        <ResponsiveTooltip>
          <ResponsiveTooltipTrigger>Hover me</ResponsiveTooltipTrigger>
          <ResponsiveTooltipContent>Tooltip content</ResponsiveTooltipContent>
        </ResponsiveTooltip>,
        {
          pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
        },
      );

      // ASSERT
      expect(screen.getByText(/hover me/i)).toBeVisible();
    });

    it('given the user hovers over the trigger, shows the tooltip content', async () => {
      // ARRANGE
      render(
        <ResponsiveTooltip>
          <ResponsiveTooltipTrigger>Hover me</ResponsiveTooltipTrigger>
          <ResponsiveTooltipContent>Tooltip content</ResponsiveTooltipContent>
        </ResponsiveTooltip>,
        {
          pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
        },
      );

      // ACT
      await userEvent.hover(screen.getByText(/hover me/i));

      // ASSERT
      // Radix renders the content twice (visible + screen-reader span).
      const tooltipContents = await screen.findAllByText(/tooltip content/i);
      expect(tooltipContents.length).toBeGreaterThanOrEqual(1);
      expect(tooltipContents[0]).toBeInTheDocument();
    });
  });

  describe('Mobile behavior', () => {
    it('renders the trigger element', () => {
      // ARRANGE
      render(
        <ResponsiveTooltip>
          <ResponsiveTooltipTrigger>Tap me</ResponsiveTooltipTrigger>
          <ResponsiveTooltipContent>Popover content</ResponsiveTooltipContent>
        </ResponsiveTooltip>,
        {
          pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
        },
      );

      // ASSERT
      expect(screen.getByText(/tap me/i)).toBeVisible();
    });

    it('given the user taps the trigger, shows the popover content', async () => {
      // ARRANGE
      render(
        <ResponsiveTooltip>
          <ResponsiveTooltipTrigger>Tap me</ResponsiveTooltipTrigger>
          <ResponsiveTooltipContent>Popover content</ResponsiveTooltipContent>
        </ResponsiveTooltip>,
        {
          pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
        },
      );

      // ACT
      await userEvent.click(screen.getByText(/tap me/i));

      // ASSERT
      expect(await screen.findByText(/popover content/i)).toBeVisible();
    });

    it('given the popover is open and the user taps outside, closes the popover', async () => {
      // ARRANGE
      render(
        <div>
          <ResponsiveTooltip>
            <ResponsiveTooltipTrigger>Tap me</ResponsiveTooltipTrigger>
            <ResponsiveTooltipContent>Popover content</ResponsiveTooltipContent>
          </ResponsiveTooltip>
          <button>Outside button</button>
        </div>,
        {
          pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
        },
      );

      // Open the popover.
      await userEvent.click(screen.getByText(/tap me/i));
      expect(await screen.findByText(/popover content/i)).toBeVisible();

      // ACT
      await userEvent.click(screen.getByText(/outside button/i));

      // ASSERT
      expect(screen.queryByText(/popover content/i)).not.toBeInTheDocument();
    });
  });
});
