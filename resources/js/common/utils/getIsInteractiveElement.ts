import {
  type ComponentType,
  type ElementType,
  type FC,
  isValidElement,
  type ReactNode,
} from 'react';

export function getIsInteractiveElement(children: ReactNode): boolean {
  if (!isValidElement(children)) {
    return false;
  }

  // Check if the element type matches interactive elements.
  const isInteractiveType = (type: string): boolean =>
    ['button', 'a', 'input', 'select', 'textarea'].includes(type.toLowerCase());

  // Check if the component name indicates it's interactive.
  const isInteractiveComponent = (component: ElementType): boolean => {
    const componentName = (component as FC).displayName || (component as FC).name;

    return [
      'BaseButton',
      'InertiaLink',
      'Popover',
      'Dialog',
      'BaseSelectTrigger',
      'BaseSwitch',
    ].some((name) => componentName.includes(name));
  };

  // For native elements, just check the HTML tag.
  if (typeof children.type === 'string') {
    return isInteractiveType(children.type);
  }

  // Check if the component itself is interactive.
  if (isInteractiveComponent(children.type)) {
    return true;
  }

  // For custom components, check if they render to an interactive element.
  const props = children.props as {
    role?: string;
    onClick?: unknown;
    href?: string;
    as?: string | ComponentType;
  };

  return !!(props.onClick || props.href || props.role === 'button');
}
