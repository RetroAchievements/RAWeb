import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createUserCredits } from '@/test/factories';

import { DesignCreditsDisplay } from './DesignCreditsDisplay';

describe('Component: DesignCreditsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DesignCreditsDisplay
        designCredits={[]}
        hashCompatibilityTestingCredits={[]}
        testingCredits={[]}
        writingCredits={[]}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given design credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const designCredits = [
      createUserCredits({ displayName: 'Alice', count: 5 }),
      createUserCredits({ displayName: 'Bob', count: 3 }),
    ];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
        hashCompatibilityTestingCredits={[]}
        testingCredits={[]}
        writingCredits={[]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement design\/ideas/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
  });

  it('given testing credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const testingCredits = [
      createUserCredits({ displayName: 'Charlie' }),
      createUserCredits({ displayName: 'David' }),
    ];

    render(
      <DesignCreditsDisplay
        designCredits={[]}
        hashCompatibilityTestingCredits={[]}
        testingCredits={testingCredits}
        writingCredits={[]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/playtesters/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Charlie')[0]).toBeVisible();
    expect(screen.getAllByText('David')[0]).toBeVisible();
  });

  it('given writing credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const writingCredits = [
      createUserCredits({ displayName: 'Eve', count: 10 }),
      createUserCredits({ displayName: 'Frank', count: 7 }),
    ];

    render(
      <DesignCreditsDisplay
        designCredits={[]}
        hashCompatibilityTestingCredits={[]}
        testingCredits={[]}
        writingCredits={writingCredits}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/writing contributions/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Eve')[0]).toBeVisible();
    expect(screen.getAllByText('Frank')[0]).toBeVisible();
  });

  it('given all credit types have users, shows all sections in the tooltip', async () => {
    // ARRANGE
    const designCredits = [createUserCredits({ displayName: 'Alice', count: 5 })];
    const testingCredits = [createUserCredits({ displayName: 'Bob' })];
    const writingCredits = [createUserCredits({ displayName: 'Charlie', count: 3 })];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
        hashCompatibilityTestingCredits={[]}
        testingCredits={testingCredits}
        writingCredits={writingCredits}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement design\/ideas/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/playtesters/i)[0]).toBeVisible();
    expect(screen.getAllByText(/writing contributions/i)[0]).toBeVisible();
  });

  it('given the same user appears in multiple credit types, only shows them once in the avatar stack', () => {
    // ARRANGE
    const sharedUserData = { displayName: 'Alice', count: 5 };
    const sharedUser = createUserCredits(sharedUserData);
    const designCredits = [sharedUser, createUserCredits({ displayName: 'Bob', count: 3 })];
    const testingCredits = [createUserCredits(sharedUserData)];
    const writingCredits = [createUserCredits({ displayName: 'Charlie', count: 10 })];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
        hashCompatibilityTestingCredits={[]}
        testingCredits={testingCredits}
        writingCredits={writingCredits}
      />,
    );

    // ASSERT
    // ... 3 unique users (Alice, Bob, Charlie) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(3);
  });

  it('given no credits of a certain type, does not show that section in the tooltip', async () => {
    // ARRANGE
    const designCredits = [createUserCredits({ displayName: 'Alice', count: 5 })];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
        hashCompatibilityTestingCredits={[]}
        testingCredits={[]}
        writingCredits={[]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement design\/ideas/i)[0]).toBeVisible();
    });
    expect(screen.queryByText(/playtesters/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/writing contributions/i)).not.toBeInTheDocument();
  });

  it('given hash compatibility testing credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const hashCompatibilityTestingCredits = [
      createUserCredits({ displayName: 'Alice', dateCredited: '2024-01-15' }),
      createUserCredits({ displayName: 'Bob', dateCredited: '2024-01-20' }),
    ];

    render(
      <DesignCreditsDisplay
        designCredits={[]}
        hashCompatibilityTestingCredits={hashCompatibilityTestingCredits}
        testingCredits={[]}
        writingCredits={[]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/hash compatibility testing/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
  });

  it('shows hash compatibility credits in the avatar stack', () => {
    // ARRANGE
    const hashCompatibilityTestingCredits = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
    ];
    const designCredits = [createUserCredits({ displayName: 'Charlie' })];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
        hashCompatibilityTestingCredits={hashCompatibilityTestingCredits}
        testingCredits={[]}
        writingCredits={[]}
      />,
    );

    // ASSERT
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(3);
  });

  it('given the same user appears in hash compatibility and other credits, only shows them once', () => {
    // ARRANGE
    const sharedUser = createUserCredits({ displayName: 'Scott' });
    const hashCompatibilityTestingCredits = [sharedUser]; // !! same user in hash compatibility
    const testingCredits = [sharedUser]; // !! and testing credits

    render(
      <DesignCreditsDisplay
        designCredits={[]}
        hashCompatibilityTestingCredits={hashCompatibilityTestingCredits}
        testingCredits={testingCredits}
        writingCredits={[]}
      />,
    );

    // ASSERT
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(1);
  });
});
