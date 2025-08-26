import { render } from '@/test';
import { createStaticData } from '@/test/factories';

import { HomeSidebar } from './HomeSidebar';

describe('Component: HomeSidebar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HomeSidebar />, {
      pageProps: {
        staticData: createStaticData(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
