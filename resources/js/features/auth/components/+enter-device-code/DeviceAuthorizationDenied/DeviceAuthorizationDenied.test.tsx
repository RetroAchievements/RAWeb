import { render, screen } from '@/test';

import { DeviceAuthorizationDenied } from './DeviceAuthorizationDenied';

describe('Component: DeviceAuthorizationDenied', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DeviceAuthorizationDenied />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct copy', () => {
    // ARRANGE
    render(<DeviceAuthorizationDenied />);

    // ASSERT
    expect(screen.getByText(/denied/i)).toBeVisible();
    expect(screen.getByText(/you can close this window/i)).toBeVisible();
  });
});
