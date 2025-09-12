import { render } from '@/test';

import { GamePageLink } from './GamePageLink';

describe('Component: GamePageLink', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GamePageLink game={1}>Link</GamePageLink>);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
