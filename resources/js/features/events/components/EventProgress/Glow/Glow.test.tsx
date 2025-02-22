import { render, screen } from '@/test';

import { Glow } from './Glow';

describe('Component: Glow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<Glow isMastered={false} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game is not mastered, renders with the correct styling', () => {
    // ARRANGE
    render(<Glow isMastered={false} />);

    // ASSERT
    const element = screen.getByTestId('progress-blur');
    expect(element).toHaveClass('inset-[5px]');
    expect(element).toHaveClass('group-hover:inset-[3px]');
    expect(element).toHaveClass('from-zinc-400');
    expect(element).toHaveClass('to-slate-500');
  });

  it('given the game is mastered, renders with the mastered styling', () => {
    // ARRANGE
    render(<Glow isMastered={true} />);

    // ASSERT
    const element = screen.getByTestId('progress-blur');
    expect(element).toHaveClass('inset-[7px]');
    expect(element).toHaveClass('group-hover:inset-[5px]');
    expect(element).toHaveClass('from-yellow-400');
    expect(element).toHaveClass('to-amber-400');
  });
});
