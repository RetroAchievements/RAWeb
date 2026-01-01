import { render, screen } from '@/test';

import { DeviceAuthorizationSuccess } from './DeviceAuthorizationSuccess';

vi.mock('../../OAuthPageLayout', () => ({
  OAuthPageLayout: ({ children, glowVariant }: any) => (
    <div data-testid="oauth-page-layout" data-glow-variant={glowVariant}>
      {children}
    </div>
  ),
}));

describe('Component: DeviceAuthorizationSuccess', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DeviceAuthorizationSuccess />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct copy', () => {
    // ARRANGE
    render(<DeviceAuthorizationSuccess />);

    // ASSERT
    expect(screen.getByText(/authorized/i)).toBeVisible();
    expect(screen.getByText(/you can close this window/i)).toBeVisible();
  });
});
