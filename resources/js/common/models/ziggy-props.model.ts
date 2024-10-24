/**
 * @see HandleInertiaRequests.php
 */

export interface ZiggyProps {
  defaults: unknown[];
  device: 'mobile' | 'desktop';
  location: string;
  port: number;
  query: Record<string, string | Record<string, string>>;
  url: string;
}
