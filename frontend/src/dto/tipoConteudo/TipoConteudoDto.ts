export interface TipoConteudoDto {
  id: number; instituicaoId: number; instituicaoNome: string; responsavelId: number; responsavelNome: string
  nome: string; descricao: string | null; ativo: boolean; criadoEm: string; atualizadoEm: string
}
export interface SalvarTipoConteudoDto { instituicaoId: number; responsavelId: number; nome: string; descricao: string | null; ativo: boolean }
export interface ListaTiposConteudoDto { itens: TipoConteudoDto[]; total: number; pagina: number; porPagina: number }
