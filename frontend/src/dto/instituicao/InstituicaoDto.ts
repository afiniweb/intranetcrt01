export interface InstituicaoDto {
  id: number
  nome: string
  sigla: string
  cnpj: string | null
  ativo: boolean
  criadoEm: string
  atualizadoEm: string
}

export interface SalvarInstituicaoDto {
  nome: string
  sigla: string
  cnpj: string | null
  ativo: boolean
}

export interface ListaInstituicoesDto {
  itens: InstituicaoDto[]
  total: number
  pagina: number
  porPagina: number
}
