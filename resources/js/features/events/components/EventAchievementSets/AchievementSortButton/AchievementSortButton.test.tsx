import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';

import { render, screen } from '@/test';

import { AchievementSortButton } from './AchievementSortButton';

describe('Component: AchievementSortButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ASSERT
    expect(() =>
      render(<AchievementSortButton value="displayOrder" onChange={onChange} />),
    ).not.toThrow();
  });

  it('given display order sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ACT
    render(<AchievementSortButton value="displayOrder" onChange={onChange} />);

    // ASSERT
    expect(screen.getByText(/display order/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given reverse display order sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ACT
    render(<AchievementSortButton value="-displayOrder" onChange={onChange} />);

    // ASSERT
    expect(screen.getByText(/display order/i)).toBeVisible();
    expect(screen.getByTestId('sort-descending-icon')).toBeVisible();
  });

  it('given won by sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ACT
    render(<AchievementSortButton value="wonBy" onChange={onChange} />);

    // ASSERT
    expect(screen.getByText(/won by/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given reverse won by sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ACT
    render(<AchievementSortButton value="-wonBy" onChange={onChange} />);

    // ASSERT
    expect(screen.getByText(/won by/i)).toBeVisible();
    expect(screen.getByTestId('sort-descending-icon')).toBeVisible();
  });

  it('given active sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    // ACT
    render(<AchievementSortButton value="active" onChange={onChange} includeActiveOption />);

    // ASSERT
    expect(screen.getByText(/status/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given includeActiveOption is false, does not show the status option in dropdown', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="displayOrder" onChange={onChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(screen.queryByRole('menuitemcheckbox', { name: /status/i })).not.toBeInTheDocument();
  });

  it('given includeActiveOption is true, shows the status option in dropdown', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(
      <AchievementSortButton value="displayOrder" onChange={onChange} includeActiveOption={true} />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(screen.getByRole('menuitemcheckbox', { name: /status/i })).toBeVisible();
  });

  it('when clicking the status sort option, calls onChange with active', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="displayOrder" onChange={onChange} includeActiveOption />);

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /^status$/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('active');
  });

  it('when clicking display order (first) option, calls onChange with displayOrder', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="wonBy" onChange={onChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(
      screen.getByRole('menuitemcheckbox', { name: /display order \(first\)/i }),
    );

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('displayOrder');
  });

  it('when clicking display order (last) option, calls onChange with -displayOrder', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="wonBy" onChange={onChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(
      screen.getByRole('menuitemcheckbox', { name: /display order \(last\)/i }),
    );

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('-displayOrder');
  });

  it('when clicking won by (most) option, calls onChange with wonBy', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="displayOrder" onChange={onChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /won by \(most\)/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('wonBy');
  });

  it('when clicking won by (least) option, calls onChange with -wonBy', async () => {
    // ARRANGE
    const onChange = vi.fn();
    render(<AchievementSortButton value="displayOrder" onChange={onChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /won by \(least\)/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('-wonBy');
  });
});
