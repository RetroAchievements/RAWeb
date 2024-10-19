import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import { createSystem } from '@/test/factories';

import { SystemChip } from './SystemChip';

// Suppress the error message thrown from not having a short name or icon url.
console.error = vi.fn();

describe('Component: SystemChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SystemChip {...createSystem()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no system short name or icon url, throws', () => {
    // ARRANGE
    const system = createSystem({ iconUrl: undefined, nameShort: undefined });

    // ASSERT
    expect(() => render(<SystemChip {...system} />)).toThrowError();
  });

  it('given the correct fields are set, renders the icon and label', () => {
    // ARRANGE
    const system = createSystem({ iconUrl: faker.internet.url(), nameShort: 'GB' });

    render(<SystemChip {...system} />);

    // ASSERT
    expect(screen.getByRole('img', { name: system.nameShort })).toBeVisible();
    expect(screen.getByText(/gb/i)).toBeVisible();
  });
});
