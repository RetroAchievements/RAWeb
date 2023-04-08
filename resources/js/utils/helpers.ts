export const config = (key: string) => window?.cfg?.[key];

export const asset = (uri: string) => `${config('assetsUrl')}${uri}`;
