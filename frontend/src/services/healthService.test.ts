import { beforeEach, describe, expect, it, vi } from 'vitest'
import { httpClient } from './httpClient'
import { healthService } from './healthService'

vi.mock('./httpClient', () => ({
  httpClient: {
    get: vi.fn(),
  },
}))

describe('healthService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('checks and returns the API status', async () => {
    vi.mocked(httpClient.get).mockResolvedValue({
      data: {
        status: 'ok',
        service: 'intranet-crt01-api',
        version: '0.1.0',
      },
    })

    const response = await healthService.check()

    expect(httpClient.get).toHaveBeenCalledWith('/health')
    expect(response.status).toBe('ok')
  })
})
