import { render, screen } from '@/test';
import { createUserCredits } from '@/test/factories';

import { TooltipCreditRow } from './TooltipCreditRow';

describe('Component: TooltipCreditRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TooltipCreditRow credit={createUserCredits()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a credit object, displays the avatar and display name', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'Scott',
    });

    render(<TooltipCreditRow credit={credit} />);

    // ASSERT
    const avatar = screen.getByRole('img');
    expect(avatar).toBeVisible();
    expect(avatar).toHaveAttribute('src', expect.stringContaining(credit.avatarUrl));

    expect(screen.getByText(/scott/i)).toBeVisible();
  });

  it('given showAchievementCount is false, does not display the achievement count', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'Scott',
      count: 42,
    });

    render(<TooltipCreditRow credit={credit} showAchievementCount={false} />);

    // ASSERT
    expect(screen.queryByLabelText(/achievements/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/42/i)).not.toBeInTheDocument();
  });

  it('given showAchievementCount is true, displays the formatted achievement count with trophy icon', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'Scott',
      count: 1234,
    });

    render(<TooltipCreditRow credit={credit} showAchievementCount={true} />);

    // ASSERT
    expect(screen.getByText(/1,234/i)).toBeVisible();
    expect(screen.getByLabelText(/achievements/i)).toBeVisible();
  });

  it('given showCreditDate is false, does not display the credit date', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'John Doe',
      dateCredited: '2024-01-15T00:00:00Z',
    });

    render(<TooltipCreditRow credit={credit} showCreditDate={false} />);

    // ASSERT
    expect(screen.queryByText(/2024/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/01\/15/i)).not.toBeInTheDocument();
  });

  it('given showCreditDate is true, displays the formatted credit date', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'John Doe',
      dateCredited: '2024-01-15T00:00:00Z',
    });

    render(<TooltipCreditRow credit={credit} showCreditDate={true} />);

    // ASSERT
    const dateElement = screen.getByText((content, element) => {
      return element?.className === 'text-neutral-500' && content.includes('/');
    });
    expect(dateElement).toBeVisible();
  });

  it('given both showAchievementCount and showCreditDate are true, displays both elements', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'John Doe',
      count: 42,
      dateCredited: '2024-01-15T00:00:00Z',
    });

    render(<TooltipCreditRow credit={credit} showAchievementCount={true} showCreditDate={true} />);

    // ASSERT
    expect(screen.getByText(/john doe/i)).toBeVisible();
    expect(screen.getByText(/42/i)).toBeVisible();
    expect(screen.getByLabelText(/achievements/i)).toBeVisible();

    const dateElement = screen.getByText((content, element) => {
      return element?.className === 'text-neutral-500' && content.includes('/');
    });
    expect(dateElement).toBeVisible();
  });

  it('given default props, does not show achievement count or credit date', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'John Doe',
      count: 42,
      dateCredited: '2024-01-15T00:00:00Z',
    });

    render(<TooltipCreditRow credit={credit} />);

    // ASSERT
    expect(screen.getByText(/john doe/i)).toBeVisible();

    expect(screen.queryByLabelText(/achievements/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/42/i)).not.toBeInTheDocument();

    expect(screen.queryByText(/2024/i)).not.toBeInTheDocument();
  });

  it('given a credit with zero count and showAchievementCount is true, displays zero', () => {
    // ARRANGE
    const credit = createUserCredits({
      displayName: 'John Doe',
      count: 0, // !!
    });

    render(<TooltipCreditRow credit={credit} showAchievementCount={true} />);

    // ASSERT
    expect(screen.getByText(/0/i)).toBeVisible();
    expect(screen.getByLabelText(/achievements/i)).toBeVisible();
  });
});
