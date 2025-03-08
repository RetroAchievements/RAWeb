import { createElement } from 'react';

import { render, screen } from '@/test';
import { createRaEvent } from '@/test/factories';

import { EventShowSidebarRoot } from './EventShowSidebarRoot';

vi.mock('recharts', async (importOriginal) => {
  const originalModule = (await importOriginal()) as Record<string, unknown>;

  return {
    ...originalModule,
    ResponsiveContainer: () => createElement('div'),
  };
});

describe('Component: EventShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();

    const { container } = render(<EventShowSidebarRoot />, {
      pageProps: { event, playerAchievementChartBuckets: [], topAchievers: [] },
    });

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByTestId('sidebar')).toBeVisible();
  });
});
