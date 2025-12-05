import { render, screen } from '@/test';

import { SubsetTag } from './SubsetTag';

describe('Component: SubsetTag', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SubsetTag />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no type is provided, displays "Subset"', () => {
    // ARRANGE
    render(<SubsetTag />);

    // ASSERT
    expect(screen.getByText(/subset/i)).toBeVisible();

    expect(screen.queryByText(/bonus/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/specialty/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/exclusive/i)).not.toBeInTheDocument();
  });

  it('given the type is "bonus", displays "Bonus Subset"', () => {
    // ARRANGE
    render(<SubsetTag type="bonus" />);

    // ASSERT
    expect(screen.getByText(/bonus subset/i)).toBeVisible();
  });

  it('given the type is "specialty", displays "Specialty Subset"', () => {
    // ARRANGE
    render(<SubsetTag type="specialty" />);

    // ASSERT
    expect(screen.getByText(/specialty subset/i)).toBeVisible();
  });

  it('given the type is "exclusive", displays "Exclusive Subset"', () => {
    // ARRANGE
    render(<SubsetTag type="exclusive" />);

    // ASSERT
    expect(screen.getByText(/exclusive subset/i)).toBeVisible();
  });

  it('given a custom className is provided, applies it to the chip', () => {
    // ARRANGE
    render(<SubsetTag className="custom-test-class" />);

    // ASSERT
    const chipElement = screen.getByText(/subset/i);
    expect(chipElement).toHaveClass('custom-test-class');
  });

  it('given the type is "core", displays "Subset"', () => {
    // ARRANGE
    render(<SubsetTag type="core" />);

    // ASSERT
    expect(screen.getByText(/subset/i)).toBeVisible();

    expect(screen.queryByText(/bonus/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/specialty/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/exclusive/i)).not.toBeInTheDocument();
  });
});
