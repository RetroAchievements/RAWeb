import { render } from '@/test';

import { MilestonePointsBox } from './MilestonePointsBox';

describe('Component: MilestonePointsBox', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MilestonePointsBox totalPoints={1_000_000_001} />);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
