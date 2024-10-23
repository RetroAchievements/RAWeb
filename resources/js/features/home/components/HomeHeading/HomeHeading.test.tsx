import { render, screen } from '@/test';

import { HomeHeading } from './HomeHeading';

describe('Component: HomeHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HomeHeading>Label</HomeHeading>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading element', () => {
    // ARRANGE
    render(<HomeHeading>Label</HomeHeading>);

    // ASSERT
    expect(screen.getByRole('heading', { name: /label/i })).toBeVisible();
  });
});
