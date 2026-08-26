import type { ListaPublicacoesDto, PublicacaoDto, SalvarPublicacaoDto } from '../dto/publicacao/PublicacaoDto'
import { httpClient } from './httpClient'

export const publicacaoService = {
  async listar(busca = '', pagina = 1, porPagina = 100): Promise<ListaPublicacoesDto> { return (await httpClient.get<ListaPublicacoesDto>('/publicacoes', { params: { busca, pagina, porPagina } })).data },
  async criar(dados: SalvarPublicacaoDto, arquivo: File | null, progresso?: (percentual: number) => void): Promise<PublicacaoDto> { const formulario = new FormData(); formulario.append('tipoConteudoId', String(dados.tipoConteudoId)); formulario.append('titulo', dados.titulo); formulario.append('corpo', dados.corpo); if (dados.anexoUrl) formulario.append('anexoUrl', dados.anexoUrl); if (arquivo) formulario.append('arquivo', arquivo); return (await httpClient.post<PublicacaoDto>('/publicacoes', formulario, { onUploadProgress: evento => { if (evento.total) progresso?.(Math.round((evento.loaded * 100) / evento.total)) } })).data },
  async arquivar(id: number): Promise<PublicacaoDto> { return (await httpClient.delete<PublicacaoDto>(`/publicacoes/${id}`)).data },
  async reativar(id: number): Promise<PublicacaoDto> { return (await httpClient.post<PublicacaoDto>(`/publicacoes/${id}/reativar`)).data },
}
