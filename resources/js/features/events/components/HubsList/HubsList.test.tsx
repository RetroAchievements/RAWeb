import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { HubsList } from './HubsList';

describe('Component: HubsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HubsList hubs={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no hubs, renders nothing', () => {
    // ARRANGE
    render(<HubsList hubs={[]} />);

    // ASSERT
    expect(screen.queryByTestId('hubs-list')).not.toBeInTheDocument();
  });

  it('given there are hubs, renders the hubs list with the correct title', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: 'AAAAA' }), createGameSet({ title: 'BBBBB' })];

    render(<HubsList hubs={mockHubs} />);

    // ASSERT
    expect(screen.getByTestId('hubs-list')).toBeVisible();
    expect(screen.getByText(/hubs/i)).toBeVisible();

    expect(screen.getAllByRole('listitem')).toHaveLength(2);
    expect(screen.getByText(/aaaaa/i)).toBeVisible();
    expect(screen.getByText(/bbbbb/i)).toBeVisible();
  });

  it('cleans hub titles', () => {
    // ARRANGE
    const mockHubs = [createGameSet({ title: '[Events - Achievement of the Week]' })];

    render(<HubsList hubs={mockHubs} />);

    // ASSERT
    expect(screen.queryByText(/\[/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/\]/i)).not.toBeInTheDocument();
    expect(screen.getByText('Events - Achievement of the Week')).toBeVisible();
  });
});
