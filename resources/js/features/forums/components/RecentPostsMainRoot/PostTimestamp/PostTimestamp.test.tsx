import { faker } from '@faker-js/faker';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';

import { PostTimestamp } from './PostTimestamp';

dayjs.extend(utc);

describe('Component: PostTimestamp', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PostTimestamp asAbsoluteDate={false} postedAt={faker.date.recent().toISOString()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given `asAbsoluteDate` is false, renders a relative date', () => {
    // ARRANGE
    const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
    vi.setSystemTime(mockCurrentDate);

    const mockPostDate = dayjs.utc('2024-05-04T05:08.00').toDate();

    render(<PostTimestamp asAbsoluteDate={false} postedAt={mockPostDate.toISOString()} />);

    // ASSERT
    expect(screen.getByText(/4 days ago/i)).toBeVisible();
  });

  it('given `asAbsoluteDate` is true, renders an absolute date', () => {
    // ARRANGE
    const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
    vi.setSystemTime(mockCurrentDate);

    const mockPostDate = dayjs.utc('2024-05-04T05:08.00').toDate();

    render(<PostTimestamp asAbsoluteDate={true} postedAt={mockPostDate.toISOString()} />);

    // ASSERT
    expect(screen.getByText(/04 May 2024, 05:08/i)).toBeVisible();
  });
});
