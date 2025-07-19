/* eslint-disable no-restricted-imports -- base components can import from radix-ui */

import { Collapsible as CollapsiblePrimitive } from 'radix-ui';

const BaseCollapsible = CollapsiblePrimitive.Root;

const BaseCollapsibleTrigger = CollapsiblePrimitive.CollapsibleTrigger;

const BaseCollapsibleContent = CollapsiblePrimitive.CollapsibleContent;

export { BaseCollapsible, BaseCollapsibleContent, BaseCollapsibleTrigger };
