import { render } from '@/test';

import { AppProviders } from './AppProviders';

describe('Component: AppProviders', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AppProviders>content</AppProviders>, { wrapper: () => <></> });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
