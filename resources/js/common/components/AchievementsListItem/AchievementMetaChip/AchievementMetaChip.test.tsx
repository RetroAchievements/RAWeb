import { render } from '@/test';

import { AchievementMetaChip } from './AchievementMetaChip';

describe('AchievementMetaChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementMetaChip>Test Content</AchievementMetaChip>);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
