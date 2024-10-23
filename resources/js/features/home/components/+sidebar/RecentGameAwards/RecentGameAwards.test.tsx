import { render } from '@/test';

import { RecentGameAwards } from './RecentGameAwards';

describe('Component: RecentGameAwards', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentGameAwards />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it.todo('displays the correct timestamp for the beaten award');
  it.todo('displays the correct game for the beaten award');
  it.todo('displays the correct user for the beaten award');

  it.todo('displays the correct timestamp for the mastery award');
  it.todo('displays the correct game for the mastery award');
  it.todo('displays the correct user for the mastery award');
});
