import type { ListaPublicacoesDashboardDto, TipoConteudoDashboardDto } from '../dto/dashboard/DashboardDto'
import { httpClient } from './httpClient'
export const dashboardService = {
  async listarTipos(): Promise<TipoConteudoDashboardDto[]> { return (await httpClient.get<TipoConteudoDashboardDto[]>('/dashboard/tipos-conteudo')).data },
  async listarPublicacoes(tipoId: number, pagina = 1, porPagina = 10): Promise<ListaPublicacoesDashboardDto> { return (await httpClient.get<ListaPublicacoesDashboardDto>(`/dashboard/tipos-conteudo/${tipoId}/publicacoes`, { params: { pagina, porPagina } })).data },
}
