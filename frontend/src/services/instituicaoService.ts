import type { InstituicaoDto, ListaInstituicoesDto, SalvarInstituicaoDto } from '../dto/instituicao/InstituicaoDto'
import { httpClient } from './httpClient'

export const instituicaoService = {
  async listar(busca = '', pagina = 1, porPagina = 10): Promise<ListaInstituicoesDto> {
    const response = await httpClient.get<ListaInstituicoesDto>('/instituicoes', { params: { busca, pagina, porPagina } })
    return response.data
  },
  async criar(dados: SalvarInstituicaoDto): Promise<InstituicaoDto> {
    const response = await httpClient.post<InstituicaoDto>('/instituicoes', dados)
    return response.data
  },
  async atualizar(id: number, dados: SalvarInstituicaoDto): Promise<InstituicaoDto> {
    const response = await httpClient.put<InstituicaoDto>(`/instituicoes/${id}`, dados)
    return response.data
  },
  async excluir(id: number): Promise<void> { await httpClient.delete(`/instituicoes/${id}`) },
}
