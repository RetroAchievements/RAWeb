/* eslint-disable @typescript-eslint/no-explicit-any */

/**
 * @see https://github.com/JiLiZART/BBob/issues/125#issuecomment-1774257527
 */

/**
 * Plugin that converts line breaks to `<br/>` tags.
 * Multiple consecutive newlines will be folded down to maximum of 2.
 * After spoiler tags, only one br tag is allowed.
 */
import { isEOL } from '@bbob/plugin-helper';

/**
 * Checks if input is an object.
 * @param value input
 * @returns if value is an object
 */
const isObj = (value: any) => typeof value === 'object';

/**
 * Creates a br tag node.
 * @returns br tag object
 */
const createBrNode = () => ({
  tag: 'br',
  content: null,
});

/**
 * Checks if node is a br tag.
 * @param node node to check
 * @returns true if node is a br tag
 */
const isBrTag = (node: any): boolean => {
  return isObj(node) && node.tag === 'br';
};

/**
 * Checks if a node is a spoiler block based on its structure.
 * @param node node to check
 * @returns true if node is a spoiler block
 */
const isSpoilerBlock = (node: any): boolean => {
  return isObj(node) && 'start' in node && 'end' in node && 'content' in node;
};

/**
 * Process a sequence of nodes to handle br tags appropriately.
 * @param nodes array of nodes to process
 * @returns processed array with correct br tag placement
 */
const processNodeSequence = (nodes: any[]): any[] => {
  const result: any[] = [];
  let consecutiveBrs = 0;
  let afterSpoiler = false;

  for (let i = 0; i < nodes.length; i++) {
    const node = nodes[i];
    const isNewline = isEOL(node);

    // Handle non-newline nodes
    if (!isNewline) {
      if (isSpoilerBlock(node)) {
        // Process spoiler content
        if (node.content) {
          node.content = Array.isArray(node.content)
            ? processNodeSequence(node.content)
            : node.content;
        }
        result.push(node);
        afterSpoiler = true;
        consecutiveBrs = 0;
        continue;
      }

      // Reset counters for regular content
      if (!isBrTag(node)) {
        afterSpoiler = false;
        consecutiveBrs = 0;
      }

      result.push(node);
      continue;
    }

    // Handle newlines
    if (afterSpoiler) {
      // Only allow one br after spoiler
      if (consecutiveBrs === 0) {
        result.push(createBrNode(), '\n');
        consecutiveBrs++;
      }
    } else {
      // Allow up to two brs elsewhere
      if (consecutiveBrs < 2) {
        result.push(createBrNode(), '\n');
        consecutiveBrs++;
      }
    }
  }

  return result;
};

/**
 * Converts `\n` to `<br/>` self closing tag. Supply this as the last plugin in the preset lists.
 * Limits consecutive newlines to 2, except after spoiler blocks where limit is 1.
 *
 * @example converts all line breaks to br
 * ```ts
 * const output = bbob([preset(), lineBreakPlugin()]).process(input, {render}).html
 * ```
 * @example will not convert line breaks inside [nobr]
 * ```ts
 * const nobr = (node: TagNode) => {return { disableLineBreakConversion: true, content: node.content }}; \\ tag in preset
 * ...
 * const output = bbob([preset(), lineBreakPlugin()]).process(input, {render}).html
 * ```
 * @returns plugin to be used in BBob process
 */
export const bbobLineBreakPlugin = () => {
  return (tree: any): any => {
    if (Array.isArray(tree)) {
      return processNodeSequence(tree);
    }

    return tree;
  };
};
