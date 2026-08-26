import type { ListaUsuariosDto, SalvarUsuarioDto, UsuarioDto } from '../dto/usuario/UsuarioDto'
import { httpClient } from './httpClient'
export const usuarioService = {
  async listar(busca = '', pagina = 1, porPagina = 10): Promise<ListaUsuariosDto> { return (await httpClient.get<ListaUsuariosDto>('/usuarios', { params: { busca, pagina, porPagina } })).data },
  async criar(dados: SalvarUsuarioDto): Promise<UsuarioDto> { return (await httpClient.post<UsuarioDto>('/usuarios', dados)).data },
  async atualizar(id: number, dados: SalvarUsuarioDto): Promise<UsuarioDto> { return (await httpClient.put<UsuarioDto>(`/usuarios/${id}`, dados)).data },
  async excluir(id: number): Promise<void> { await httpClient.delete(`/usuarios/${id}`) },
}
