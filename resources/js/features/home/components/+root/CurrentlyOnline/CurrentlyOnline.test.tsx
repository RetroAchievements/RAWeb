import { render, screen } from '@/test';

import { CurrentlyOnline } from './CurrentlyOnline';

// recharts is going to throw errors in JSDOM that we don't care about.
console.warn = vi.fn();

describe('Component: CurrentlyOnline', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CurrentlyOnline />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<CurrentlyOnline />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /currently online/i })).toBeVisible();
  });

  it.todo('displays a label showing the count of currently online players');
  it.todo('displays a label showing the all-time high count of online players');
});
