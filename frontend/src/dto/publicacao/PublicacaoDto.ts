export type StatusPublicacao = 'PUBLICADA' | 'ARQUIVADA'
export interface PublicacaoDto {
  id: number; tipoConteudoId: number; tipoConteudo: string; titulo: string; corpo: string; anexoUrl: string | null; arquivoUrl: string | null
  status: string; autorNome: string; instituicaoNome: string; escritorioNome: string; publicadaEm: string | null; criadoEm: string; atualizadoEm: string
}
export interface SalvarPublicacaoDto { tipoConteudoId: number; titulo: string; corpo: string; anexoUrl: string | null }
export interface ListaPublicacoesDto { itens: PublicacaoDto[]; total: number; pagina: number; porPagina: number }
