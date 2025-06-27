import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createUserCredits } from '@/test/factories';

import { DesignCreditsDisplay } from './DesignCreditsDisplay';

describe('Component: DesignCreditsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DesignCreditsDisplay designCredits={[]} testingCredits={[]} writingCredits={[]} />,
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

  it('given no credits of a certain type, does not show that section in the tooltip', async () => {
    // ARRANGE
    const designCredits = [createUserCredits({ displayName: 'Alice', count: 5 })];

    render(
      <DesignCreditsDisplay
        designCredits={designCredits}
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
});
