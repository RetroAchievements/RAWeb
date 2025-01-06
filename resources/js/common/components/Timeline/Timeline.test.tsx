import { render, screen } from '@/test';

import { Timeline, TimelineItem } from './Timeline';

describe('Component: Timeline', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <Timeline>
        <TimelineItem label="Test">Content</TimelineItem>
      </Timeline>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('can render multiple timeline items', () => {
    // ARRANGE
    render(
      <Timeline>
        <TimelineItem label="First">First content</TimelineItem>
        <TimelineItem label="Second">Second content</TimelineItem>
        <TimelineItem label="Third">Third content</TimelineItem>
      </Timeline>,
    );

    // ASSERT
    expect(screen.getAllByText(/first/i)[0]).toBeVisible();
    expect(screen.getAllByText(/second/i)[0]).toBeVisible();
    expect(screen.getAllByText(/third/i)[0]).toBeVisible();
  });
});

describe('Component: TimelineItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TimelineItem label="Test">Content</TimelineItem>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the label', () => {
    // ARRANGE
    render(<TimelineItem label="Important Event">Content</TimelineItem>);

    // ASSERT
    expect(screen.getAllByText(/important event/i)[0]).toBeVisible();
  });

  it('displays children content', () => {
    // ARRANGE
    render(
      <TimelineItem label="Test">
        <div>Custom content here</div>
      </TimelineItem>,
    );

    // ASSERT
    expect(screen.getAllByText(/custom content here/i)[0]).toBeVisible();
  });
});
