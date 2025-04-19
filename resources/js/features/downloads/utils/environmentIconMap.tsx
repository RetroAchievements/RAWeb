import type { ReactNode } from 'react';
import {
  LuCpu,
  LuGamepad,
  LuGamepad2,
  LuLaptop,
  LuMicrochip,
  LuSmartphone,
  LuSquareMousePointer,
} from 'react-icons/lu';

export const environmentIconMap: Record<
  App.Platform.Enums.PlatformExecutionEnvironment,
  ReactNode
> = {
  console: <LuGamepad2 className="size-4" />,
  desktop: <LuLaptop className="size-4" />,
  embedded: <LuMicrochip className="size-4" />,
  mobile: <LuSmartphone className="size-4" />,
  original_hardware: <LuGamepad className="size-4" />,
  single_board: <LuCpu className="size-4" />,
  web: <LuSquareMousePointer className="size-4" />,
};
