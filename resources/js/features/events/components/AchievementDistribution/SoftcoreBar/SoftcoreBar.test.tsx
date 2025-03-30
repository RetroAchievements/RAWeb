import { render, screen } from '@/test';

import { SoftcoreBar } from './SoftcoreBar';

vi.mock('recharts', () => ({
  Bar: ({ 'data-testid': dataTestId }: { 'data-testid': string }) => (
    <div data-testid={dataTestId}>Mocked Bar</div>
  ),
}));

describe('Component: SoftcoreBar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SoftcoreBar variant="game" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the variant is game, renders the softcore bar component', () => {
    // ARRANGE
    render(<SoftcoreBar variant="game" />);

    // ASSERT
    expect(screen.getByTestId(/softcore-bar/i)).toBeVisible();
  });

  it('given the variant is event, does not render anything', () => {
    // ARRANGE
    render(<SoftcoreBar variant="event" />);

    // ASSERT
    expect(screen.queryByTestId(/softcore-bar/i)).not.toBeInTheDocument();
  });
});
