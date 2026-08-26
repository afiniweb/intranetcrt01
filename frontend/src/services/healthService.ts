import type { HealthResponseDto } from '../dto/health/HealthResponseDto'
import { httpClient } from './httpClient'

export const healthService = {
  async check(): Promise<HealthResponseDto> {
    const response = await httpClient.get<HealthResponseDto>('/health')

    return response.data
  },
}

