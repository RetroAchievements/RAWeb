import { render } from '@/test';

import { HomeSidebar } from './HomeSidebar';

describe('Component: HomeSidebar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HomeSidebar />);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
