import type { AxiosError, AxiosResponse } from 'axios';

interface LaravelValidationResponse extends AxiosResponse {
  status: 422;
  data: {
    message: string;
    errors: Record<string, string[]>;
  };
}

export interface LaravelValidationError extends AxiosError {
  response: LaravelValidationResponse;
}
