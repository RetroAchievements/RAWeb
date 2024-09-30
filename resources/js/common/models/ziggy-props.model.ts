/**
 * @see HandleInertiaRequests.php
 */

export interface ZiggyProps {
  defaults: unknown[];
  location: string;
  port: number;
  query: Record<string, string | Record<string, string>>;
  url: string;
}
