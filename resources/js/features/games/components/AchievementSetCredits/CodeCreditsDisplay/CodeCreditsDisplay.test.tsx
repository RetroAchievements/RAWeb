import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createUserCredits } from '@/test/factories';

import { CodeCreditsDisplay } from './CodeCreditsDisplay';

describe('Component: CodeCreditsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CodeCreditsDisplay logicCredits={[]} maintainerCredits={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given maintainer credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const maintainerCredits = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
    ];

    render(<CodeCreditsDisplay logicCredits={[]} maintainerCredits={maintainerCredits} />);

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement maintainers/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
  });

  it('given logic credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const logicCredits = [
      createUserCredits({ displayName: 'Charlie', count: 5 }),
      createUserCredits({ displayName: 'David', count: 3 }),
    ];

    render(<CodeCreditsDisplay logicCredits={logicCredits} maintainerCredits={[]} />);

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/code contributors/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Charlie')[0]).toBeVisible();
    expect(screen.getAllByText('David')[0]).toBeVisible();
  });

  it('given both credit types have users, shows all sections in the tooltip', async () => {
    // ARRANGE
    const maintainerCredits = [createUserCredits({ displayName: 'Alice' })];
    const logicCredits = [createUserCredits({ displayName: 'Bob', count: 5 })];

    render(
      <CodeCreditsDisplay logicCredits={logicCredits} maintainerCredits={maintainerCredits} />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement maintainers/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/code contributors/i)[0]).toBeVisible();
  });

  it('given the same user appears in multiple credit types, only shows them once in the avatar stack', () => {
    // ARRANGE
    const sharedUserData = { displayName: 'Alice' };
    const maintainerCredits = [createUserCredits(sharedUserData)];
    const logicCredits = [
      createUserCredits(sharedUserData),
      createUserCredits({ displayName: 'Bob', count: 5 }),
    ];

    render(
      <CodeCreditsDisplay logicCredits={logicCredits} maintainerCredits={maintainerCredits} />,
    );

    // ASSERT
    // ... should show 2 unique users (Alice, Bob) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(2);
  });

  it('given no credits of a certain type, does not show that section in the tooltip', async () => {
    // ARRANGE
    const maintainerCredits = [createUserCredits({ displayName: 'Alice' })];

    render(<CodeCreditsDisplay logicCredits={[]} maintainerCredits={maintainerCredits} />);

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement maintainers/i)[0]).toBeVisible();
    });
    expect(screen.queryByText(/code contributors/i)).not.toBeInTheDocument();
  });
});
