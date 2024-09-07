import dayjs from 'dayjs';

export function diffForHumans(date: string, from?: string): string {
  const start = dayjs(from);
  const end = dayjs(date);
  const diff = Math.abs(start.diff(end, 'second'));

  if (diff < 1) {
    return 'now';
  } else if (diff < 60) {
    return `${Math.floor(diff)} second${diff === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else if (diff < 3600) {
    const minutes = Math.floor(diff / 60);

    return `${minutes} minute${minutes === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else if (diff < 86400) {
    const hours = Math.floor(diff / 3600);

    return `${hours} hour${hours === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else if (diff < 604800) {
    const days = Math.floor(diff / 86400);

    return `${days} day${days === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else if (diff < 2629743) {
    const weeks = Math.floor(diff / 597800);

    return `${weeks} week${weeks === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else if (diff < 31556926) {
    const months = Math.floor(diff / 2628243);

    return `${months} month${months === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  } else {
    const years = Math.floor(diff / 31556926);

    return `${years} year${years === 1 ? '' : 's'} ${start.isAfter(end) ? 'ago' : 'from now'}`;
  }
}
