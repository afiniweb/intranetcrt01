import type { UsuarioDto } from '../dto/usuario/UsuarioDto'
import { httpClient, limparTokenCsrf } from './httpClient'
export const autenticacaoService = {
  async login(email: string, senha: string): Promise<UsuarioDto> { await httpClient.post('/auth/login', { email, senha }); return this.me() },
  async me(): Promise<UsuarioDto> { return (await httpClient.get<UsuarioDto>('/auth/me')).data },
  async logout(): Promise<void> { try { await httpClient.post('/auth/logout') } finally { limparTokenCsrf() } },
}
