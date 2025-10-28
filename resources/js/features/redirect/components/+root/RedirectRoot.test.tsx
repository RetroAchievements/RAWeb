import { expect } from 'vitest';

import { render } from '@/test';

import { RedirectRoot } from './RedirectRoot';

describe('Component: RedirectRoot', () => {
  it('renders without crashing', () => {
    // ACT
    const { container } = render(<RedirectRoot />, {
      pageProps: {
        url: 'https://retroachievements.org',
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
