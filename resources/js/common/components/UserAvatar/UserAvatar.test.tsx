import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';

import { UserAvatar } from './UserAvatar';

describe('Component: UserAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserAvatar displayName={faker.internet.displayName()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a displayName, shows the displayName on the screen', () => {
    // ARRANGE
    const displayName = faker.internet.displayName();

    render(<UserAvatar displayName={displayName} />);

    // ASSERT
    expect(screen.getByText(displayName)).toBeVisible();
  });

  it('given there is no displayName, still renders successfully', () => {
    // ARRANGE
    render(<UserAvatar displayName={null} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /deleted user/i })).toBeVisible();
  });

  it('applies the correct size to the image', () => {
    // ARRANGE
    render(<UserAvatar displayName={faker.internet.displayName()} size={8} />);

    // ASSERT
    const imgEl = screen.getByRole('img');

    expect(imgEl).toHaveAttribute('width', '8');
    expect(imgEl).toHaveAttribute('height', '8');
  });

  it('adds card tooltip props by default', () => {
    // ARRANGE
    render(<UserAvatar displayName="Scott" />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).toHaveAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'user', dynamicId: 'Scott', dynamicContext: 'undefined'})",
    );
    expect(anchorEl).toHaveAttribute('x-on:mouseover', 'showTooltip($event)');
    expect(anchorEl).toHaveAttribute('x-on:mouseleave', 'hideTooltip');
    expect(anchorEl).toHaveAttribute('x-on:mousemove', 'trackMouseMovement($event)');
  });

  it('does not add card tooltip props when `hasTooltip` is false', () => {
    // ARRANGE
    render(<UserAvatar displayName="Scott" hasTooltip={false} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAccessibleDescription('x-on:mousemove');
  });
});
