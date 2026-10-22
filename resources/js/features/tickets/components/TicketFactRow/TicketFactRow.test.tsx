import type { IconType } from 'react-icons/lib';

import { render, screen } from '@/test';

import { TicketFactRow } from './TicketFactRow';

describe('Component: TicketFactRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const StubFactIcon: IconType = () => <svg />;

    const { container } = render(
      <ul>
        <TicketFactRow Icon={StubFactIcon}>Some fact</TicketFactRow>
      </ul>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an icon and children, shows both in a list item', () => {
    // ARRANGE
    const StubFactIcon: IconType = () => <svg data-testid="stub-fact-icon" />;

    render(
      <ul>
        <TicketFactRow Icon={StubFactIcon}>Some fact</TicketFactRow>
      </ul>,
    );

    // ASSERT
    expect(screen.getByRole('listitem')).toHaveTextContent('Some fact');
    expect(screen.getByTestId('stub-fact-icon')).toBeInTheDocument();
  });
});
