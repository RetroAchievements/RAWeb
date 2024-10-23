import {
  Children,
  cloneElement,
  type FC,
  isValidElement,
  type ReactElement,
  type ReactNode,
} from 'react';

import { useMockableLaravelReactI18n } from './useMockableLaravelReactI18n';

interface TransProps {
  /** The key used to look up the translation string in the locale JSON file. */
  i18nKey: string;

  /** An object of values to interpolate into the translation string. */
  values?: Record<string, string | number>;
  /** The count used for pluralization (eg: singular vs. plural forms). */
  count?: number;
  /** The React elements to be inserted into the translation string. */
  children?: ReactNode;
}

/**
 * Component for handling translations with dynamic content and elements.
 *
 * This component takes an `i18nKey`, optional `values` for interpolation,
 * and optional `count` for pluralization. It also accepts `children`,
 * which can include React elements to be inserted into the translated string.
 */
export const Trans: FC<TransProps> = ({ i18nKey, values = {}, count, children }) => {
  const { t } = useMockableLaravelReactI18n();

  // Merge `count` into `values` if provided, often used for pluralization.
  const interpolationValues = count !== undefined ? { ...values, count } : values;

  // Adjust the translation key for pluralization if `count` is provided.
  const translationKey =
    count !== undefined ? `${i18nKey}_${count === 1 ? 'one' : 'other'}` : i18nKey;

  // Retrieve the translation string.
  const rawTranslation = t(translationKey, interpolationValues);

  // If the translation is missing or equals the key, fallback to `children`.
  if (!rawTranslation || rawTranslation === translationKey) {
    return <>{children}</>;
  }

  // If the translation contains no element tags, return it as plain text.
  if (!rawTranslation.includes('<')) {
    return <>{rawTranslation}</>;
  }

  // Extract all React elements from `children` to map them into the translation.
  const childElements = flattenReactElements(children);

  // Map element indices to their corresponding React elements.
  const elementMap: Record<number, ReactElement> = {};
  for (const [index, element] of childElements.entries()) {
    elementMap[index] = element;
  }

  // Split the translation string into tokens (text and element tags).
  const tokenRegex = /(<[0-9]+>|<\/[0-9]+>)/;
  const tokens = rawTranslation.split(tokenRegex).filter(Boolean);

  /**
   * Stack frame interface for reconstructing the nested elements.
   */
  interface StackFrame {
    /** The React element corresponding to the current tag, or null for the root. */
    element: ReactElement | null;
    /** The child nodes accumulated under the current element. */
    children: ReactNode[];
  }

  // Initialize the stack with a root frame.
  const stack: StackFrame[] = [{ element: null, children: [] }];

  // Process each token to reconstruct the nested React elements.
  for (const token of tokens) {
    const openTagMatch = token.match(/^<([0-9]+)>$/);
    const closeTagMatch = token.match(/^<\/([0-9]+)>$/);

    if (openTagMatch) {
      // Opening tag found. Push a new frame onto the stack.
      const index = parseInt(openTagMatch[1], 10);
      const element = elementMap[index] || null;
      stack.push({ element, children: [] });
    } else if (closeTagMatch) {
      // Closing tag found. Pop the current frame and attach it to the parent.
      const frame = stack.pop()!;
      const parentFrame = stack[stack.length - 1];
      const { element, children } = frame;

      const newElement = element
        ? cloneElement(element, { key: `element-${stack.length}` }, children)
        : children;

      parentFrame.children.push(newElement);
    } else {
      // Plain text content. Add it to the current frame's children.
      stack[stack.length - 1].children.push(token);
    }
  }

  // The final result is the children accumulated in the root frame.
  const translatedContent = stack[0].children;

  // Render the content.
  return <>{translatedContent}</>;
};

/**
 * Recursively flattens React children and collects all
 * valid React elements.
 *
 * @param children The ReactNode children to flatten.
 * @returns An array of ReactElement entities.
 */
function flattenReactElements(children: ReactNode): ReactElement[] {
  const elements: ReactElement[] = [];

  function recurse(child: ReactNode): void {
    if (isValidElement(child)) {
      elements.push(child);
      if (child.props && child.props.children) {
        Children.forEach(child.props.children, recurse);
      }
    }
  }

  Children.forEach(children, recurse);

  return elements;
}
