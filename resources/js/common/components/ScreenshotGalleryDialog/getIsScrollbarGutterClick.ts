import type { MouseEvent } from 'react';

export function getIsScrollbarGutterClick(event: MouseEvent<HTMLElement>): boolean {
  const { clientWidth, offsetWidth } = event.currentTarget;
  const hasScrollbar = offsetWidth > clientWidth;
  if (!hasScrollbar) {
    return false;
  }

  return event.nativeEvent.offsetX >= clientWidth;
}
