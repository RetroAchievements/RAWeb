import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { UserAvatar } from './UserAvatar';

describe('Component: UserAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserAvatar {...createUser()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a displayName, shows the displayName on the screen', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render(<UserAvatar {...user} />);

    // ASSERT
    expect(screen.getByText(/scott/i)).toBeVisible();
  });

  it('given there is no displayName, still renders successfully', () => {
    // ARRANGE
    const user = createUser({ displayName: undefined });

    render(<UserAvatar {...user} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /deleted user/i })).toBeVisible();
  });

  it('applies the correct size to the image', () => {
    // ARRANGE
    const user = createUser();

    render(<UserAvatar {...user} size={8} />);

    // ASSERT
    const imgEl = screen.getByRole('img');

    expect(imgEl).toHaveAttribute('width', '8');
    expect(imgEl).toHaveAttribute('height', '8');
  });

  it('adds card tooltip props by default', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render(<UserAvatar {...user} />);

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
    const user = createUser({ displayName: 'Scott' });

    render(<UserAvatar {...user} hasTooltip={false} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAccessibleDescription('x-on:mousemove');
  });
});
