import type { ListaTiposConteudoDto, SalvarTipoConteudoDto, TipoConteudoDto } from '../dto/tipoConteudo/TipoConteudoDto'
import { httpClient } from './httpClient'

export const tipoConteudoService = {
  async listar(busca = '', pagina = 1, porPagina = 10): Promise<ListaTiposConteudoDto> { return (await httpClient.get<ListaTiposConteudoDto>('/tipos-conteudo', { params: { busca, pagina, porPagina } })).data },
  async criar(dados: SalvarTipoConteudoDto): Promise<TipoConteudoDto> { return (await httpClient.post<TipoConteudoDto>('/tipos-conteudo', dados)).data },
  async atualizar(id: number, dados: SalvarTipoConteudoDto): Promise<TipoConteudoDto> { return (await httpClient.put<TipoConteudoDto>(`/tipos-conteudo/${id}`, dados)).data },
  async excluir(id: number): Promise<void> { await httpClient.delete(`/tipos-conteudo/${id}`) },
}
