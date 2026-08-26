import type { EscritorioDto, ListaEscritoriosDto, SalvarEscritorioDto } from '../dto/escritorio/EscritorioDto'
import { httpClient } from './httpClient'

export const escritorioService = {
  async listar(busca = '', pagina = 1, porPagina = 10): Promise<ListaEscritoriosDto> { return (await httpClient.get<ListaEscritoriosDto>('/escritorios', { params: { busca, pagina, porPagina } })).data },
  async criar(dados: SalvarEscritorioDto): Promise<EscritorioDto> { return (await httpClient.post<EscritorioDto>('/escritorios', dados)).data },
  async atualizar(id: number, dados: SalvarEscritorioDto): Promise<EscritorioDto> { return (await httpClient.put<EscritorioDto>(`/escritorios/${id}`, dados)).data },
  async excluir(id: number): Promise<void> { await httpClient.delete(`/escritorios/${id}`) },
}
