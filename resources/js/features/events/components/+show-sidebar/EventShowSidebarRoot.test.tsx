import { render, screen } from '@/test';
import { createRaEvent } from '@/test/factories';

import { EventShowSidebarRoot } from './EventShowSidebarRoot';

describe('Component: EventShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();

    const { container } = render(<EventShowSidebarRoot />, {
      pageProps: { event },
    });

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByTestId('sidebar')).toBeVisible();
  });
});
