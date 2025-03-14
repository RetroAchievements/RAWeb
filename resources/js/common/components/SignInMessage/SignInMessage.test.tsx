import { render, screen } from '@/test';

import { SignInMessage } from './SignInMessage';

describe('Component: SignInMessage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SignInMessage />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component is rendered, displays the sign in message with a link', () => {
    // ARRANGE
    render(<SignInMessage />);

    // ASSERT
    expect(screen.getByText(/you must/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /sign in/i })).toBeVisible();
  });
});
