export interface TipoConteudoDashboardDto { id: number; nome: string; descricao: string | null; responsavelNome: string; responsavelPeloTipo: boolean; totalPublicadas: number; ultimaPublicacaoTitulo: string | null; ultimaPublicacaoEm: string | null }
export interface PublicacaoDashboardDto { id: number; titulo: string; corpo: string; anexoUrl: string | null; arquivoUrl: string | null; autorNome: string; escritorioNome: string; publicadaEm: string }
export interface ListaPublicacoesDashboardDto { itens: PublicacaoDashboardDto[]; total: number; pagina: number; porPagina: number }
