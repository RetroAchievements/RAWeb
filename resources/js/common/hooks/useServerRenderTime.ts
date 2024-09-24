import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { useEffect, useState } from 'react';

dayjs.extend(utc);

export function useServerRenderTime() {
  // `useState` runs on the server, so this value is populated during SSR.
  const [renderedAt, setRenderedAt] = useState(() => dayjs.utc().toISOString());

  // `useEffect` runs only on the client. The `renderedAt` state will try to
  // "drift" during hydration. Have the effect pin the value.
  useEffect(() => {
    setRenderedAt(renderedAt);
  }, [renderedAt]);

  return { renderedAt };
}
