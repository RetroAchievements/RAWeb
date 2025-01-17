/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as CollapsiblePrimitive from '@radix-ui/react-collapsible';

const BaseCollapsible = CollapsiblePrimitive.Root;

const BaseCollapsibleTrigger = CollapsiblePrimitive.CollapsibleTrigger;

const BaseCollapsibleContent = CollapsiblePrimitive.CollapsibleContent;

export { BaseCollapsible, BaseCollapsibleContent, BaseCollapsibleTrigger };
