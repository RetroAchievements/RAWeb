import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { SupporterTierSection } from './SupporterTierSection';

describe('Component: SupporterTierSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SupporterTierSection heading="$2 Supporters (0)" initialSupporters={[]} totalCount={0} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the heading', () => {
    // ARRANGE
    render(
      <SupporterTierSection heading="$2 Supporters (5)" initialSupporters={[]} totalCount={5} />,
    );

    // ASSERT
    expect(screen.getByText('$2 Supporters (5)')).toBeVisible();
  });

  it('given deferred supporters have loaded, renders both the initial and deferred supporters', () => {
    // ARRANGE
    const initialSupporter = createUser({ displayName: 'InitialSupporter' });
    const deferredSupporter = createUser({ displayName: 'DeferredSupporter' });

    render(
      <SupporterTierSection
        heading="$1 Supporters (2)"
        initialSupporters={[initialSupporter]}
        deferredSupporters={[deferredSupporter]}
        totalCount={2}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /initialsupporter/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /deferredsupporter/i })).toBeVisible();
    expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
  });

  it('given the deferred supporters have not loaded yet, displays a loading state', () => {
    // ARRANGE
    render(
      <SupporterTierSection
        heading="$1 Supporters (10)"
        initialSupporters={[createUser()]}
        deferredSupporters={null}
        totalCount={10} // !! more than the 1 we're showing
      />,
    );

    // ASSERT
    expect(screen.getByText(/loading/i)).toBeVisible();
  });

  it('given every supporter is already in the initial batch, does not display a loading state', () => {
    // ARRANGE
    render(
      <SupporterTierSection
        heading="$1 Supporters (1)"
        initialSupporters={[createUser()]}
        deferredSupporters={null}
        totalCount={1}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/loading/i)).not.toBeInTheDocument();
  });
});
