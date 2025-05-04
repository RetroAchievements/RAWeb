import { LuWrench } from 'react-icons/lu';

import { render, screen } from '@/test';

import { PlayableSidebarButton } from './PlayableSidebarButton';

describe('Component: PlayableSidebarButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayableSidebarButton href="#" IconComponent={LuWrench} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an icon component and text, renders both correctly', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByText(/settings/i)).toBeVisible();
    expect(screen.getByRole('link')).toHaveTextContent(/settings/i);
  });

  it('given a count prop, displays it as a chip', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench} count={5}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByText('5')).toBeVisible();
  });

  it('given no count prop, does not render a chip', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.queryByRole('status')).not.toBeInTheDocument();
  });

  it('given a href prop, renders a link with that href', () => {
    // ARRANGE
    const testUrl = '/settings';
    render(
      <PlayableSidebarButton href={testUrl} IconComponent={LuWrench}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    const linkElement = screen.getByRole('link');
    expect(linkElement).toHaveAttribute('href', testUrl);
  });

  it('given a target prop, sets the target attribute correctly', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench} target="_blank">
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByRole('link')).toHaveAttribute('target', '_blank');
  });

  it('given isInertiaLink is true, still render a link', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href={route('home')} IconComponent={LuWrench} isInertiaLink>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByRole('link')).toBeVisible();
  });

  it('given isInertiaLink is false, renders a regular anchor tag without prefetch', () => {
    // ARRANGE
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench} isInertiaLink={false}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByRole('link')).not.toHaveAttribute('prefetch');
  });

  it('given a className prop, applies it to the element', () => {
    // ARRANGE
    const customClass = 'custom-test-class';
    render(
      <PlayableSidebarButton href="#" IconComponent={LuWrench} className={customClass}>
        Settings
      </PlayableSidebarButton>,
    );

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass(customClass);
  });
});
