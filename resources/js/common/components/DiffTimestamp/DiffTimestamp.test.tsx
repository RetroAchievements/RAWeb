import { faker } from '@faker-js/faker';
import userEvent from '@testing-library/user-event';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';

import { DiffTimestamp } from './DiffTimestamp';

dayjs.extend(utc);

describe('Component: DiffTimestamp', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DiffTimestamp asAbsoluteDate={false} at={faker.date.recent().toISOString()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given `asAbsoluteDate` is false, renders a relative date', () => {
    // ARRANGE
    const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
    vi.setSystemTime(mockCurrentDate);

    const mockPostDate = dayjs.utc('2024-05-04T05:08.00').toDate();

    render(<DiffTimestamp asAbsoluteDate={false} at={mockPostDate.toISOString()} />);

    // ASSERT
    expect(screen.getByText(/3 days ago/i)).toBeVisible();
  });

  it('given `asAbsoluteDate` is true, renders an absolute date', () => {
    // ARRANGE
    const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
    vi.setSystemTime(mockCurrentDate);

    const mockPostDate = dayjs.utc('2024-05-04T05:08.00').toDate();

    render(<DiffTimestamp asAbsoluteDate={true} at={mockPostDate.toISOString()} />);

    // ASSERT
    expect(screen.getByText(/May 04, 2024, 05:08/i)).toBeVisible();
  });

  it('renders the month divide correctly', () => {
    // ARRANGE
    const mockCurrentDate = dayjs('2024-07-20T23:41:24Z');
    vi.setSystemTime(mockCurrentDate.toDate());

    const mockPostOneDate = dayjs('2024-05-21T03:37:57.000Z');
    const mockPostTwoDate = dayjs('2024-05-21T03:19:31.000Z');

    render(
      <div>
        <DiffTimestamp asAbsoluteDate={false} at={mockPostOneDate.toISOString()} />
        <DiffTimestamp asAbsoluteDate={false} at={mockPostTwoDate.toISOString()} />
      </div>,
    );

    // ASSERT
    expect(screen.getByText(/1 month ago/i)).toBeVisible();
  });

  it('displays a formatted timestamp on hover', async () => {
    // ARRANGE
    const user = userEvent.setup();

    const mockCurrentDate = dayjs.utc('2024-05-08').toDate();
    vi.setSystemTime(mockCurrentDate);

    const mockPostDate = dayjs.utc('2024-05-04T05:08.00').toDate();

    render(<DiffTimestamp asAbsoluteDate={false} at={mockPostDate.toISOString()} />);

    // ACT
    await user.hover(screen.getByText(/3 days ago/i));

    // ASSERT
    expect(await screen.findByRole('tooltip', { name: /may 4, 2024/i })).toBeVisible();
  });
});
