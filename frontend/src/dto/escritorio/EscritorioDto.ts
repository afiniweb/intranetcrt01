export interface EscritorioDto {
  id: number
  instituicaoId: number
  instituicaoNome: string
  nome: string
  uf: string
  cidade: string
  endereco: string | null
  ativo: boolean
  criadoEm: string
  atualizadoEm: string
}
export interface SalvarEscritorioDto { instituicaoId: number; nome: string; uf: string; cidade: string; endereco: string | null; ativo: boolean }
export interface ListaEscritoriosDto { itens: EscritorioDto[]; total: number; pagina: number; porPagina: number }
