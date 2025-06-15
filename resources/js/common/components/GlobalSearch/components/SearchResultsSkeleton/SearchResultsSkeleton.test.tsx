import { BaseCommand } from '@/common/components/+vendor/BaseCommand';
import { render } from '@/test';

import { SearchResultsSkeleton } from './SearchResultsSkeleton';

describe('Component: SearchResultsSkeleton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseCommand>
        <SearchResultsSkeleton />
      </BaseCommand>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
});
